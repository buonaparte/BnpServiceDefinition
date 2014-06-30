<?php

namespace BnpServiceDefinition\Factory\LanguageExtension;

use BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider;
use BnpServiceDefinition\Options\DefinitionOptions;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ConfigFunctionProviderFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var $options DefinitionOptions */
        $options = $serviceLocator->get('BnpServiceDefinition\Options\DefinitionOptions');

        return new ConfigFunctionProvider($options);
    }
}