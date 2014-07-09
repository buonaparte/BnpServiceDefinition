<?php

namespace BnpServiceDefinition\Factory;

use BnpServiceDefinition\Options\DefinitionOptions;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DefinitionOptionsFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var $config array */
        $config = $serviceLocator->get('Config');
        $optionsConfig = isset($config['bnp-service-definition'])
            ? $config['bnp-service-definition']
            : array();

        return new DefinitionOptions($optionsConfig);
    }
}
