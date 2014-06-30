<?php

namespace BnpServiceDefinition\Factory;

use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Service\Evaluator;
use BnpServiceDefinition\Service\ReferenceResolver;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class EvaluatorFactory implements FactoryInterface
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
        $language = $serviceLocator->get('BnpServiceFactory\Dsl\Language');
        /** @var $referenceResolver ReferenceResolver */
        $referenceResolver = $serviceLocator->get('BnpServiceFactory\Service\ReferenceResolver');

        return new Evaluator($language, $referenceResolver);
    }
}