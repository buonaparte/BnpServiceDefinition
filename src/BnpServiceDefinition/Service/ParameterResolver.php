<?php

namespace BnpServiceDefinition\Service;

use BnpServiceDefinition\Parameter\ParameterInterface;
use BnpServiceDefinition\Exception;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\Exception as ServiceManagerException;
use Zend\Stdlib\PriorityQueue;

class ParameterResolver extends AbstractPluginManager
{
    /**
     * @var string
     */
    protected $defaultResolvedType = 'value';

    public function __construct(ConfigInterface $configuration = null)
    {
        parent::__construct($configuration);

        $plugins = array(
            'BnpServiceDefinition\Parameter\ValueParameter' => 'value',
            'BnpServiceDefinition\Parameter\ConfigParameter' => 'config',
            'BnpServiceDefinition\Parameter\ServiceParameter' => 'service',
            'BnpServiceDefinition\Parameter\DslParameter' => 'dsl'
        );

        foreach ($plugins as $plugin => $alias) {
            $this->setInvokableClass($plugin, $plugin);
            $this->setAlias($alias, $plugin);
        }
    }

    public function resolveParameter($parameter, $compile = true)
    {
        if (! is_array($parameter)) {
            $parameter = array('type' => $this->defaultResolvedType, 'value' => $parameter);
        }

        if (! is_array($parameter) || empty($parameter['type']) || ! array_key_exists('value', $parameter)) {
            throw new Exception\InvalidArgumentException(
                'Parameter to resolve must be an array containing at least type and value specified'
            );
        }

        if (! $compile) {
            if (! array_key_exists('order', $parameter)) {
                $parameter['order'] = 0;
            }

            return $parameter;
        }

        /** @var $reference \BnpServiceDefinition\Parameter\ParameterInterface */
        $reference = $this->get($parameter['type']);
        return $reference->compile($parameter['value']);
    }

    public function resolveParameters(array $parameters = array())
    {
        $i = 0;
        foreach ($parameters as &$parameter) {
            $parameter = $this->resolveParameter($parameter, false);
            $parameter['__strong_order'] = $i++;
        }

        usort($parameters, function ($first, $second) {
            return $first['order'] == $second['order']
                ? ($first['__strong_order'] > $second['__strong_order'])
                : ($first['order'] > $second['order'] ? 1 : -1);
        });

        return array_map(array($this, 'resolveParameter'), $parameters);
    }

    /**
     * @param string $defaultResolvedType
     * @throws ServiceManagerException\RuntimeException
     */
    public function setDefaultResolvedType($defaultResolvedType)
    {
        if (! $this->has($defaultResolvedType)) {
            throw new ServiceManagerException\RuntimeException(sprintf(
                'undefined type (unknown plugin) %s',
                $defaultResolvedType
            ));
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
     * @throws ServiceManagerException\RuntimeException if invalid
     */
    public function validatePlugin($plugin)
    {
        if (! $plugin instanceof ParameterInterface) {
            throw new ServiceManagerException\RuntimeException(sprintf(
                '%s expects retrieving a valid %s instance, %s resolved',
                get_class($this),
                'ReferenceInterface',
                get_class($plugin)
            ));
        }
    }
}
