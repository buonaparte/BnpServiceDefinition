<?php

namespace BnpServiceDefinition;

use BnpServiceDefinition\Reference\ConfigReference;
use BnpServiceDefinition\Reference\ReferenceInterface;
use BnpServiceDefinition\Reference\ServiceReference;
use BnpServiceDefinition\Reference\ValueReference;

class ReferenceResolver
{
    protected $references = array();

    public function __construct()
    {
        $this->registerReference(new ConfigReference());
        $this->registerReference(new ServiceReference());
        $this->registerReference(new ValueReference());
    }

    public function registerReference(ReferenceInterface $reference)
    {
        $this->references[$reference::getType()] = $reference;
    }

    public function resolveReference($parameter)
    {
        if (is_string($parameter)) {
            $parameter = array('type' => 'value', 'value' => $parameter);
        }

        if (! is_array($parameter) || empty($parameter['type']) || ! array_key_exists('value', $parameter)) {
            throw new \RuntimeException();
        }

        if (! array_key_exists($parameter['type'], $this->references)) {
            throw new \RuntimeException();
        }

        /** @var $reference ReferenceInterface */
        $reference = $this->references[$parameter['type']];
        return $reference->compile($parameter['value']);
    }

    public function resolveReferences(array $parameters = array())
    {
        return array_map(array($this, 'resolveReference'), $parameters);
    }
}