<?php

namespace BnpServiceDefinition\Factory;

use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Options\DefinitionOptions;
use BnpServiceDefinition\Service\Generator;
use BnpServiceDefinition\Service\ReferenceResolver;
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
        /** @var $referenceResolver ReferenceResolver */
        $referenceResolver = $serviceLocator->get('BnpServiceDefinition\Service\ReferenceResolver');
        /** @var $options DefinitionOptions */
        $options = $serviceLocator->get('BnpServiceDefinition\Options\DefinitionOptions');

        return new Generator($language, $referenceResolver, $options);
    }
}