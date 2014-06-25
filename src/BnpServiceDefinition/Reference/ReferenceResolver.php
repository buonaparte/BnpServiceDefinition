<?php

namespace BnpServiceDefinition\Reference;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\Exception;

class ReferenceResolver extends AbstractPluginManager
{
    public function __construct(ConfigInterface $configuration = null)
    {
        parent::__construct($configuration);

        $this->setAlias(ValueReference::getType(), 'BnpServiceDefinition\Reference\ValueReference');
        $this->setAlias(ConfigReference::getType(), 'BnpServiceDefinition\Reference\ConfigReference');
        $this->setAlias(ServiceReference::getType(), 'BnpServiceDefinition\Reference\ServiceReference');
    }

    public function resolveReference($parameter)
    {
        if (is_string($parameter)) {
            $parameter = array('type' => 'value', 'value' => $parameter);
        }

        if (! is_array($parameter) || empty($parameter['type']) || ! array_key_exists('value', $parameter)) {
            throw new \RuntimeException();
        }

        /** @var $reference ReferenceInterface */
        $reference = $this->get($parameter['type']);
        return $reference->compile($parameter['value']);
    }

    public function resolveReferences(array $parameters = array())
    {
        return array_map(array($this, 'resolveReference'), $parameters);
    }

    /**
     * Validate the plugin
     *
     * Checks that the filter loaded is either a valid callback or an instance
     * of FilterInterface.
     *
     * @param  mixed $plugin
     * @return void
     * @throws Exception\RuntimeException if invalid
     */
    public function validatePlugin($plugin)
    {
        if (! $plugin instanceof ReferenceInterface) {
            throw new Exception\RuntimeException(sprintf(
                '%s expects retrieving a valid %s instance, %s resolved',
                get_class($this),
                'ReferenceInterface',
                get_class($plugin)
            ));
        }
    }
}