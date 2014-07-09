<?php

namespace BnpServiceDefinitionTest\Dsl\Extension;

use BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider;
use BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider;
use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Exception\InvalidArgumentException;
use BnpServiceDefinition\Exception\RuntimeException;
use BnpServiceDefinition\Options\DefinitionOptions;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

class ServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    /**
     * @var Language
     */
    protected $language;

    protected function setUp()
    {
        $this->services = new ServiceManager();

        $this->services->setFactory(
            'ServiceFunctionProvider',
            function (ServiceLocatorInterface $services) {
                $provider = new ServiceFunctionProvider('ServiceFunctionProvider');
                $provider->setServiceLocator($services);

                return $provider;
            }
        );

        $this->language = new Language();
        $this->language->registerExtension('ServiceFunctionProvider');
        $this->language->setServiceManager($this->services);
    }

    protected function overrideConfig(array $config = array())
    {
        $allowOverride = $this->services->getAllowOverride();

        $this->services->setAllowOverride(true);
        $configInstance = new Config($config);
        $configInstance->configureServiceManager($this->services);

        $this->services->setAllowOverride($allowOverride);
    }

    protected function getCompiledCode($part)
    {
        return sprintf('$this->services->get(\'ServiceFunctionProvider\')->getService(%s)', $part);
    }

    public function testWorks()
    {
        $this->assertTrue(true);
    }
}
