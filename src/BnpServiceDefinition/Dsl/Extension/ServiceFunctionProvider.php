<?php

namespace BnpServiceDefinition\Dsl\Extension;

use BnpServiceDefinition\Dsl\Extension\Feature\FunctionProviderInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;

class ServiceFunctionProvider implements FunctionProviderInterface
{
    /**
     * @var string
     */
    protected $serviceName;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    public function __construct($serviceName, ServiceLocatorInterface $services)
    {
        $this->serviceName = $serviceName;
        $this->services = $services;
    }

    public function getName()
    {
        return 'service';
    }

    public function getEvaluator(array $context = array())
    {
        $self = $this;
        return function ($args, $service) use ($self) {
            if (! is_string($service)) {
                return $service;
            }

            return $self->getService($service);
        };
    }

    public function getCompiler()
    {
        return function ($service) {
            if (! is_string($service)) {
                return $service;
            }

            return <<<SERVICE
\$this->services->get('{$this->serviceName}')->getService($service)
SERVICE;
        };
    }

    public function getService($name, $silent = false, $instance = null)
    {
        $service = null;
        try {
            $service = $this->services->get($name);
        } catch (ServiceNotFoundException $e) {
            if (! $silent) {
                throw $e;
            }
        } catch (ServiceNotCreatedException $e) {
            if (! $silent) {
                throw $e;
            }
        }

        if (null !== $instance and ! is_object($service) || ! $service instanceof $instance) {
            throw new \RuntimeException();
        }

        return $service;
    }
}