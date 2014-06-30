<?php

namespace BnpServiceDefinition\Factory;

use BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider;
use BnpServiceDefinition\Dsl\Extension\PluginFunctionProvider;
use BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider;
use BnpServiceDefinition\Dsl\Language;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LanguageFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $cacheKey = 'BnpServiceDefinition\Dsl\Language\Cache';
        /** @var $cache ParserCacheInterface */
        $cache = $serviceLocator->has($cacheKey) ? $serviceLocator->get($cacheKey) : null;

        $language = new Language($cache);
        $language->registerExtension(ConfigFunctionProvider::SERVICE_KEY);
        $language->registerExtension(PluginFunctionProvider::SERVICE_KEY);
        $language->registerExtension(ServiceFunctionProvider::SERVICE_KEY);

        return $language;
    }
}