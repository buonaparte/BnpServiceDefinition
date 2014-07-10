<?php

namespace BnpServiceDefinitionTest\Factory;

use BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider;
use BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider;
use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Factory\LanguageFactory;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceManager;

class LanguageFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    protected function setUp()
    {
        $this->services = new ServiceManager();
        $this->services->setFactory('language', new LanguageFactory());

        $this->services->setService('dummy_service', new \stdClass());
        $this->services->setService(
            'Config',
            array(
                'dummy_config' => 'foo'
            )
        );
    }

    /**
     * @return Language
     */
    protected function getLanguage()
    {
        $language = $this->services->get('language');
        if ($language instanceof ServiceLocatorAwareInterface) {
            $language->setServiceLocator($this->services);
        }

        return $language;
    }

    public function testInstantiationWithDefaultExtensions()
    {
        $language = $this->getLanguage();

        $this->assertInstanceOf('BnpServiceDefinition\Dsl\Language', $language);

        $configFunctionProvider = new ConfigFunctionProvider();
        $configFunctionProvider->setServiceLocator($this->services);
        $this->services->setService(ConfigFunctionProvider::SERVICE_KEY, $configFunctionProvider);

        $serviceFunctionProvider = new ServiceFunctionProvider();
        $serviceFunctionProvider->setServiceLocator($this->services);
        $this->services->setService(ServiceFunctionProvider::SERVICE_KEY, $serviceFunctionProvider);

        $this->assertSame(
            $this->services->get('dummy_service'),
            $language->evaluate("service('dummy_service')")
        );
        $this->assertEquals('foo', $language->evaluate("config('dummy_config')"));
    }
}
