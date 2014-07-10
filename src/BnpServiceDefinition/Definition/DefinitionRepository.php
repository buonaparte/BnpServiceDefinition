<?php

namespace BnpServiceDefinition\Definition;

use BnpServiceDefinition\Exception;
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
            throw new Exception\RuntimeException(sprintf(
                'Recursion detected, traversing (%s) definitions path',
                implode(' -> ', array_keys($resolvedDefinitions))
            ));
        }

        if (! array_key_exists($id, $this->definitions)) {
            throw new Exception\RuntimeException(sprintf('Service definition for id %s does not exists', $id));
        }

        $definition = ClassDefinition::fromArray($this->definitions[$id]);
        $resolvedDefinitions[$id] = $definition;

        if ($definition->hasParent()) {
            return $this->getServiceDefinition($definition->getParent(), $final, $resolvedDefinitions);
        }

        $definition = $this->constructDefinition($resolvedDefinitions);

        if ($final) {
            $this->validateDefinition($definition, $id);
        }

        return $definition;
    }

    protected function validateDefinition(ClassDefinition $classDefinition, $definitionName)
    {
        if (null === $classDefinition->getClass()) {
            throw new Exception\RuntimeException(sprintf(
                'Retrieved definition %s has no class specified',
                $definitionName
            ));
        }

        if ($classDefinition->getAbstract()) {
            throw new Exception\RuntimeException(sprintf(
                'Could not retrieve %s definition, as it is abstract',
                $definitionName
            ));
        }
    }

    protected function constructDefinition(array $resolvedDefinitions)
    {
        $compositeDefinition = array(
            'class' => null,
            'args' => array(),
            'calls' => array(),
            'abstract' => array()
        );

        foreach (array_reverse($resolvedDefinitions) as $definition) {
            /** @var $definition ClassDefinition */
            if (null !== $class = $definition->getClass()) {
                $compositeDefinition['class'] = $class;
            }

            $compositeDefinition['args'] = array_merge(
                $compositeDefinition['args'],
                $definition->getArguments()
            );

            foreach ($definition->getMethodCalls() as $method) {
                /** @var $method MethodCallDefinition */
                foreach ($compositeDefinition['calls'] as $k => $hasMethodCall) {
                    /** @var $hasMethodCall MethodCallDefinition */
                    if ($hasMethodCall->getName() == $method->getName()) {
                        $methodCallSpecs = array(
                            'name' => $method->getName(),
                            'params' => array_merge($hasMethodCall->getParameters(), $method->getParameters()),
                            'conditions' => null
                        );

                        if (null !== $hasMethodCall->getConditions()) {
                            $methodCallSpecs['conditions'] = $hasMethodCall->getConditions();
                        }

                        if (null !== $method->getConditions() && null !== $methodCallSpecs['conditions']) {
                            $methodCallSpecs['conditions'] = array_merge(
                                $methodCallSpecs['conditions'],
                                $method->getConditions()
                            );
                        } elseif (null === $methodCallSpecs['conditions']) {
                            $methodCallSpecs['conditions'] = $method->getConditions();
                        }

                        $compositeDefinition['calls'][$k] = MethodCallDefinition::fromArray($methodCallSpecs);
                        continue 2;
                    }
                }

                $compositeDefinition['calls'][] = $method;
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
