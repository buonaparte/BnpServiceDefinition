<?php

namespace BnpServiceDefinition\Definition;

use Traversable;

class DefinitionRepository implements \IteratorAggregate
{
    /**
     * @var array
     */
    protected $definitions;

    /**
     * @var
     */
    protected $terminableDefinitions;

    /**
     * @var string
     */
    protected $checksum;

    public function __construct(array $definitions)
    {
        $this->definitions = $definitions;
    }

    public function getChecksum()
    {
        if (null !== $this->checksum) {
            return $this->checksum;
        }

        return $this->checksum = hash('md5', json_encode($this->definitions));
    }

    public function hasDefinition($id)
    {
        return array_key_exists($id, $this->definitions);
    }

    public function getTerminableDefinitions()
    {
        if (null !== $this->terminableDefinitions) {
            return $this->terminableDefinitions;
        }

        $this->terminableDefinitions = array();
        foreach (array_keys($this->definitions) as $id) {
            $definition = $this->getServiceDefinition($id, false);
            if (! $definition->getAbstract()) {
                $this->terminableDefinitions[$id] = $definition;
            }
        }

        return $this->terminableDefinitions;
    }

    public function getServiceDefinition($id, $final = true, $resolvedDefinitions = array())
    {
        if (array_key_exists($id, $resolvedDefinitions)) {
            throw new \RuntimeException(sprintf(
                'Recursion detected, traversing (%s) definitions path',
                implode(' -> ', array_keys($resolvedDefinitions))
            ));
        }

        if (! array_key_exists($id, $this->definitions)) {
            throw new \RuntimeException(sprintf('Service definition for id %s does not exists', $id));
        }

        $definition = ClassDefinition::fromArray($this->definitions[$id]);
        $resolvedDefinitions[$id] = $definition;

        if ($definition->hasParent()) {
            return $this->getServiceDefinition($definition->getParent(), $final, $resolvedDefinitions);
        }

        $definition = $this->constructDefinition($resolvedDefinitions);
        $this->validateDefinition($definition, $id);

        if ($final) {
            if ($definition->getAbstract()) {
                throw new \RuntimeException(sprintf(
                    'Could not retrieve %s definition, as it is abstract',
                    $id
                ));
            }
        }

        return $definition;
    }

    protected function validateDefinition(ClassDefinition $classDefinition, $definitionName)
    {
        if (null === $classDefinition->getClass()) {
            throw new \RuntimeException(sprintf('Retrieved definition %s has no class specified', $definitionName));
        }
    }

    protected function constructDefinition(array $resolvedDefinitions)
    {
        $compositeDefinition = array(
            'class' => null,
            'arguments' => array(),
            'method_calls' => array(),
            'abstract' => array()
        );

        foreach (array_reverse($resolvedDefinitions) as $definition) {
            /** @var $definition ClassDefinition */
            if (null !== $class = $definition->getClass()) {
                $compositeDefinition['class'] = $class;
            }

            $compositeDefinition['arguments'] = array_merge(
                $compositeDefinition['arguments'],
                $definition->getArguments()
            );

            foreach ($definition->getMethodCalls() as $method) {
                /** @var $method MethodDefinition */
                foreach ($compositeDefinition['method_calls'] as $k => $hasMethodCall) {
                    /** @var $hasMethodCall MethodDefinition */
                    if ($hasMethodCall->getName() == $method->getName()) {
                        $methodCallSpecs = array(
                            'name' => $method->getName(),
                            'params' => array_merge($hasMethodCall->getParams(), $method->getParams()),
                            'condition' => null
                        );

                        if (null !== $hasMethodCall->getCondition()) {
                            $methodCallSpecs['condition'] = $hasMethodCall->getCondition();
                        }

                        if (null !== $method->getCondition() && null !== $methodCallSpecs['condition']) {
                            $methodCallSpecs['condition'] = array_merge(
                                $methodCallSpecs['condition'],
                                $method->getCondition()
                            );
                        } elseif (null === $methodCallSpecs['condition']) {
                            $methodCallSpecs['condition'] = $method->getCondition();
                        }

                        $compositeDefinition['method_calls'][$k] = MethodDefinition::fromArray($methodCallSpecs);
                        continue 2;
                    }
                }

                $compositeDefinition['method_calls'][] = $method;
            }

            $compositeDefinition['abstract'] = $definition->getAbstract();
        }

        return ClassDefinition::fromArray($compositeDefinition);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getTerminableDefinitions());
    }
}