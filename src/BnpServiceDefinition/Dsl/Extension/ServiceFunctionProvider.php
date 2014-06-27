<?php

namespace BnpServiceDefinition\Dsl\Extension;

use BnpServiceDefinition\Dsl\Extension\Feature\FunctionProviderInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ServiceFunctionProvider extends PluginFunctionProvider
{
    public function getName()
    {
        return 'service';
    }

    public function getService($name, $silent = false, $instance = null)
    {
        return $this->getServiceFromLocator(
            $this->services instanceof ServiceLocatorAwareInterface
                ? $this->services->getServiceLocator()
                : $this->services,
            $name,
            $silent,
            $instance
        );
    }
}