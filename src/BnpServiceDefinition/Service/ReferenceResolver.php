<?php

namespace BnpServiceDefinition\Service;

use BnpServiceDefinition\Reference\ReferenceInterface;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\Exception;

class ReferenceResolver extends AbstractPluginManager
{
    /**
     * @var string
     */
    protected $defaultResolvedType = 'value';

    public function __construct(ConfigInterface $configuration = null)
    {
        parent::__construct($configuration);

        $plugins = array(
            'BnpServiceDefinition\Reference\ValueReference',
            'BnpServiceDefinition\Reference\ConfigReference',
            'BnpServiceDefinition\Reference\ServiceReference',
            'BnpServiceDefinition\Reference\DslReference'
        );

        foreach ($plugins as $plugin) {
            $this->setInvokableClass($plugin, $plugin);
            $this->setAlias(call_user_func(array($plugin, 'getType')), $plugin);
        }
    }

    public function resolveReference($parameter, $compile = true)
    {
        if (! is_array($parameter)) {
            $parameter = array('type' => $this->defaultResolvedType, 'value' => $parameter);
        }

        if (! is_array($parameter) || empty($parameter['type']) || ! array_key_exists('value', $parameter)) {
            throw new \RuntimeException();
        }

        if (! $compile) {
            if (! array_key_exists('order', $parameter)) {
                $parameter['order'] = 0;
            }

            return $parameter;
        }

        /** @var $reference ReferenceInterface */
        $reference = $this->get($parameter['type']);
        return $reference->compile($parameter['value']);
    }

    public function resolveReferences(array $parameters = array())
    {
        foreach ($parameters as &$parameter) {
            $parameter = $this->resolveReference($parameter, false);
        }

        usort($parameters, function ($first, $second) {
            return $first['order'] == $second['order'] ? 0 : ($first['order'] > $second['order'] ? 1 : -1);
        });

        return array_map(array($this, 'resolveReference'), $parameters);
    }

    /**
     * @param string $defaultResolvedType
     * @throws Exception\RuntimeException
     */
    public function setDefaultResolvedType($defaultResolvedType)
    {
        if (! $this->has($defaultResolvedType)) {
            throw new Exception\RuntimeException(sprintf('undefined type (unknown plugin) %s', $defaultResolvedType));
        }

        $this->defaultResolvedType = $defaultResolvedType;
    }

    /**
     * @return string
     */
    public function getDefaultResolvedType()
    {
        return $this->defaultResolvedType;
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