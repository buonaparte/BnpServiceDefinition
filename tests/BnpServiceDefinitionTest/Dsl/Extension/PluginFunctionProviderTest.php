<?php

namespace BnpServiceDefinitionTest\Dsl\Extension;

use BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider;
use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Options\DefinitionOptions;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

class ServiceFunctionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    /**
     * @var Language
     */
    protected $language;

    /**
     * @var DefinitionOptions
     */
    protected $definitionOptions;

    protected function setUp()
    {
        $this->services = new ServiceManager(new Config(array(
            'services' => array(
                'Config' => array(
                )
            ),
        )));

        $this->services->setService('some_service', new \stdClass());
        $this->services->setService('another_service', new \ArrayObject());

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

    protected function getCompiledCode($part)
    {
        return sprintf('$this->services->get(\'ServiceFunctionProvider\')->getService(%s)', $part);
    }

    public function testCompilesSimpleServiceSpecifications()
    {
        $this->assertEquals(
            $this->getCompiledCode('"some_service", false, null'),
            $this->language->compile("service('some_service')")
        );
        $this->assertEquals(
            $this->getCompiledCode('"another_service", false, null'),
            $this->language->compile("service('another_service')")
        );
    }

    public function testCompilesServiceSpecificationsWithRestrictions()
    {
        $this->assertEquals(
            $this->getCompiledCode('"some_service", true, "\\stdClass"'),
            $this->language->compile("service('some_service', true, '\\stdClass')")
        );
        $this->assertEquals(
            $this->getCompiledCode('"another_service", false, "\\ArrayObject"'),
            $this->language->compile("service('another_service', false, '\\ArrayObject')")
        );
    }

    public function testEvaluatesSilentWithExistingServices()
    {
        $this->assertInstanceOf('\stdClass', $this->language->evaluate("service('some_service', true)"));
        $this->assertInstanceOf('\ArrayObject', $this->language->evaluate("service('another_service', true)"));
    }

    public function testEvaluatesWithInstanceChecking()
    {
        $this->assertInstanceOf('\stdClass', $this->language->evaluate("service('some_service', false, '\\stdClass')"));
        $this->assertInstanceOf(
            '\ArrayObject',
            $this->language->evaluate("service('another_service', false, '\\ArrayObject')")
        );
    }

    public function testWillSilentPassEvaluationWithNotExistingService()
    {
        $this->assertNull($this->language->evaluate("service('some_service_that_does_not_exits', true)"));
        $this->assertNull($this->language->evaluate("service('some_service_that_does_not_exits', true, '\\stdClass')"));
    }

    public function invalidServiceInstanceSpecificationsFunctionCalls()
    {
        return array(
            array("service('some_service', true, '\\ArrayObject')"),
            array("service('some_service', false, '\\ArrayObject')"),
            array("service('another_service', false, '\\stdClass')"),
            array("service('another_service', false, '\\stdObject')"),
        );
    }

    /**
     * @dataProvider invalidServiceInstanceSpecificationsFunctionCalls
     * @expectedException \RuntimeException
     */
    public function testWillThrowExceptionOnIncorrectServiceInstanceReturned($expression)
    {
        $this->language->evaluate($expression);
    }
}