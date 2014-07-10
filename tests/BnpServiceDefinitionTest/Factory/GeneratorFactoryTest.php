<?php

namespace BnpServiceDefinitionTest\Factory;

use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Factory\GeneratorFactory;
use BnpServiceDefinition\Options\DefinitionOptions;
use BnpServiceDefinition\Service\ParameterResolver;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

class GeneratorFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    protected function setUp()
    {
        $this->services = new ServiceManager();
        $this->services->setFactory('generator', new GeneratorFactory());
    }

    protected function overrideConfig(array $config)
    {
        $oldAllowOverride = $this->services->getAllowOverride();

        $this->services->setAllowOverride(true);
        $configInstance = new Config(array('services' => $config));
        $configInstance->configureServiceManager($this->services);

        $this->services->setAllowOverride($oldAllowOverride);
    }

    public function testCanCreateService()
    {
        $this->overrideConfig(array(
            'BnpServiceDefinition\Dsl\Language' => new Language(),
            'BnpServiceDefinition\Service\ParameterResolver' => new ParameterResolver(),
            'BnpServiceDefinition\Options\DefinitionOptions' => new DefinitionOptions()
        ));

        $this->assertInstanceOf('BnpServiceDefinition\Service\Generator', $this->services->get('generator'));
    }

    /**
     * @expectedException \Exception
     */
    public function testWillFailInstantiationWithNotExistingDependencies()
    {
        $this->services->get('generator');
    }
}
