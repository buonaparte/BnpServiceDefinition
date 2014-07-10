<?php

namespace BnpServiceDefinition\Dsl\Extension;

use BnpServiceDefinition\Dsl\Extension\Feature\FunctionProviderInterface;
use BnpServiceDefinition\Exception;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ConfigFunctionProvider implements
    FunctionProviderInterface,
    ServiceLocatorAwareInterface
{
    const SERVICE_KEY = 'BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider';

    /**
     * @var string
     */
    protected $serviceName;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var array
     */
    protected $config;

    public function __construct($serviceName = null)
    {
        $this->serviceName = null === $serviceName ? static::SERVICE_KEY : $serviceName;
    }

    public function getName()
    {
        return 'config';
    }

    public function getEvaluator(array $context = array())
    {
        $self = $this;
        return function ($args, $config, $silent = true, $type = null) use ($self) {
            return $self->getConfigValue($config, $silent, $type);
        };
    }

    public function getCompiler()
    {
        $serviceName = $this->serviceName;
        return function ($config, $silent = true, $type = null) use ($serviceName) {
            if ('false' === strtolower($silent)) {
                $silent = false;
            }
            $silent = $silent ? 'true' : 'false';
            if (! $type) {
                $type = 'null';
            }

            return <<<CONFIG
\$this->services->get('{$serviceName}')->getConfigValue($config, $silent, $type)
CONFIG;
        };
    }

    protected function getConfig()
    {
        if (null !== $this->config) {
            return $this->config;
        }

        $config = $this->getServiceLocator()->get('Config');
        return $this->config = empty($config) ? array() : $config;
    }

    protected function getConfigNode(array $path, $silent, $type)
    {
        $fullPath = $path;
        $config = $this->getConfig();
        while (! empty($path) && ! empty($config)) {
            $head = array_shift($path);
            $config = isset($config[$head]) ? $config[$head] : null;
        }

        if (! $silent && ! empty($path)) {
            throw new Exception\RuntimeException(sprintf(
                'Config (%s) could not be found, stopped at (%s)',
                implode(' -> ', $fullPath),
                implode(' -> ', $path)
            ));
        } elseif (! $silent && null !== $type && gettype($config) !== (string) $type) {
            throw new Exception\RuntimeException(sprintf(
                'Expected a config value of "%s", received "%s", at (%s)',
                $type,
                gettype($config),
                implode(' -> ', $fullPath)
            ));
        } elseif (! empty($path)) {
            return null;
        }

        return $config;
    }

    public function getConfigValue($config, $silent = true, $type = null)
    {
        if (is_string($config)) {
            $config = array($config);
        }

        if (! is_array($config)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Config can only be a path array, %s provided',
                gettype($config)
            ));
        }

        if (empty($config)) {
            throw new Exception\InvalidArgumentException('config cannot be an empty array');
        }

        $self = $this;
        array_walk($config, function ($part, $idx) use ($self) {
            if (! is_string($part)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'config can only contain strings as array path elements, %s received at index %d',
                    gettype($part),
                    $idx
                ));
            }
        });

        return $this->getConfigNode($config, $silent, $type);
    }

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->services;
    }
}
