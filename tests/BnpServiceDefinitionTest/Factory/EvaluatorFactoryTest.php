<?php

namespace BnpServiceDefinitionTest\Factory;

use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Factory\EvaluatorFactory;
use BnpServiceDefinition\Service\ParameterResolver;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

class EvaluatorFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    protected function setUp()
    {
        $this->services = new ServiceManager();
        $this->services->setFactory('evaluator', new EvaluatorFactory());
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
        ));

        $this->assertInstanceOf('BnpServiceDefinition\Service\Evaluator', $this->services->get('evaluator'));
    }

    /**
     * @expectedException \Exception
     */
    public function testWillFailInstantiationWithNotExistingDependencies()
    {
        $this->services->get('generator');
    }
}
