<?php

namespace BnpServiceDefinition\Factory;

use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Options\DefinitionOptions;
use BnpServiceDefinition\Service\Generator;
use BnpServiceDefinition\Service\ParameterResolver;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class GeneratorFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var $language Language */
        $language = $serviceLocator->get('BnpServiceDefinition\Dsl\Language');
        /** @var $parameterResolver ParameterResolver */
        $parameterResolver = $serviceLocator->get('BnpServiceDefinition\Service\ParameterResolver');
        /** @var $options DefinitionOptions */
        $options = $serviceLocator->get('BnpServiceDefinition\Options\DefinitionOptions');

        return new Generator($language, $parameterResolver, $options);
    }
}
