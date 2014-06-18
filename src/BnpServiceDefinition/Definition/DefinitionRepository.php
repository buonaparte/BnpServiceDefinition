<?php

namespace BnpServiceDefinition\Definition;

class DefinitionRepository
{
    /**
     * @var array
     */
    protected $definitions;

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

    public function getDefinitions()
    {
        return array_keys($this->definitions);
    }

    public function getServiceDefinition($id, $resolvedDefinitions = array())
    {
        if (array_key_exists($id, $resolvedDefinitions)) {
            throw new \RuntimeException(sprintf(
                'Recursion detected, traversing (%s) definitions path', implode(' -> ', $resolvedDefinitions)));
        }

        if (! array_key_exists($id, $this->definitions)) {
            throw new \RuntimeException(sprintf('Service definition for id %s does not exists', $id));
        }

        $definition = ClassDefinition::fromArray($this->definitions[$id]);
        if ($definition->hasParent()) {
            $resolvedDefinitions[$id] = $definition;
            return $this->getServiceDefinition($definition->getParent(), $resolvedDefinitions);
        }

        return $this->constructDefinition($resolvedDefinitions);
    }

    protected function constructDefinition(array $resolvedDefinitions)
    {
        $compositeDefinition = array(
            'class' => null,
            'arguments' => array(),
            'method_calls' => array()
        );

        foreach (array_reverse($resolvedDefinitions) as $definition) {
            /** @var $definition ClassDefinition */
            if (null === $compositeDefinition['class'] && null !== $class = $definition->getClass()) {
                $compositeDefinition['class'] = $class;
            }

            foreach ($definition->getArguments() as $argument) {
                $compositeDefinition['arguments'][] = $argument;
            }

            foreach ($definition->getMethodCalls() as $method) {
                $compositeDefinition['method_calls'][] = $method;
            }
        }

        return ClassDefinition::fromArray($compositeDefinition);
    }
}