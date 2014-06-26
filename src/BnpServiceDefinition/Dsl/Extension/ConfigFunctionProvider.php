<?php

namespace BnpServiceDefinition\Dsl\Extension;

use BnpServiceDefinition\Dsl\Extension\Feature\FunctionProviderInterface;
use BnpServiceDefinition\Options\DefinitionOptions;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\ArrayUtils;

class ConfigFunctionProvider implements FunctionProviderInterface
{
    /**
     * @var string
     */
    protected $serviceName;

    /**
     * @var DefinitionOptions
     */
    protected $options;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var array
     */
    protected $config;

    public function __construct($serviceName, DefinitionOptions $options, ServiceLocatorInterface $services)
    {
        $this->serviceName = $serviceName;
        $this->options = $options;
        $this->services = $services;
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
        return function ($config, $silent = true, $type = null) {
            if ('false' === $silent) {
                $silent = false;
            }
            $silent = $silent ? 'true' : 'false';
            if (! $type) {
                $type = 'null';
            }

            return <<<CONFIG
\$this->services->get('{$this->serviceName}')->getConfigValue($config, $silent, $type)
CONFIG;
        };
    }

    protected function getConfig()
    {
        if (null !== $this->config) {
            return $this->config;
        }

        $config = $this->services->get('Config');
        if ($config instanceof \Traversable) {
            $config = ArrayUtils::iteratorToArray($config, true);
        }

        if (empty($config) || ! is_array($config)) {
            return $this->config = array();
        }

        return $this->config = $config;
    }

    protected function getConfigNode(array $path = array(), $silent, $type)
    {
        $config = $this->getConfig();
        while (! empty($path) && ! empty($config)) {
            $head = array_shift($path);
            $config = isset($config[$head]) ? $config[$head] : null;
        }

        if (! $silent && ! empty($path)) {
            throw new \RuntimeException();
        } elseif (! $silent && null !== $type && gettype($config) !== (string) $type) {
            throw new \RuntimeException();
        } elseif (! empty($path)) {
            return null;
        }

        return $config;
    }

    protected function getConfigPath($path)
    {
        return explode($this->options->getConfigPathSeparator(), $path);
    }

    public function getConfigValue($config, $silent = true, $type = null)
    {
        if ($config instanceof \Traversable) {
            $config = ArrayUtils::iteratorToArray($config);
        }

        if (is_array($config)) {
            if (empty($config)) {
                throw new \InvalidArgumentException('config cannot be an empty array');
            }

            $self = $this;
            array_walk($config, function ($part, $idx) use ($self) {
                if (! is_string($part)) {
                    throw new \InvalidArgumentException(sprintf(
                        'config can only contain strings as array path elements, %s received at index %d',
                        gettype($part),
                        $idx
                    ));
                }
            });
        } elseif (! is_string($config)) {
            throw new \InvalidArgumentException(sprintf(
                'Config can only be a path string or array, %s provided',
                gettype($config)
            ));
        }

        return $this->getConfigNode(is_string($config) ? $this->getConfigPath($config) : $config, $silent, $type);
    }
}