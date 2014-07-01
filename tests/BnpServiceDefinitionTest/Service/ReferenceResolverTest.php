<?php

namespace BnpServiceDefinitionTest\Service;

use BnpServiceDefinition\Parameter\ConfigParameter;
use BnpServiceDefinition\Service\ParameterResolver;
use BnpServiceDefinition\Parameter\ServiceParameter;
use BnpServiceDefinition\Parameter\ValueParameter;

class ReferenceResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BnpServiceDefinition\Service\ParameterResolver
     */
    protected $resolver;

    protected function setUp()
    {
        $this->resolver = new ParameterResolver();
    }

    public function testDefaultRegisteredPluginsAreAvailable()
    {
        $this->assertInstanceOf(
            'BnpServiceDefinition\Reference\ConfigReference',
            $this->resolver->get('BnpServiceDefinition\Reference\ConfigReference')
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Reference\ServiceReference',
            $this->resolver->get('BnpServiceDefinition\Reference\ServiceReference')
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Reference\ValueReference',
            $this->resolver->get('BnpServiceDefinition\Reference\ValueReference')
        );
    }

    public function testDefaultRegisteredPluginsAreAvailableFromShortCutAliases()
    {
        $this->assertInstanceOf(
            'BnpServiceDefinition\Reference\ConfigReference',
            $this->resolver->get(ConfigParameter::getType())
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Reference\ServiceReference',
            $this->resolver->get(ServiceParameter::getType())
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Reference\ValueReference',
            $this->resolver->get(ValueParameter::getType())
        );
    }

    public function testDefaultReferenceType()
    {
        $this->assertEquals('value', $this->resolver->getDefaultResolvedType());
        $this->assertEquals("'something'", $this->resolver->resolveParameter('something'));
    }

    public function testCanChangeFallbackReferenceType()
    {
        $this->resolver->setDefaultResolvedType('value');
        $this->assertEquals('1', $this->resolver->resolveParameter(1));

        $this->resolver->setDefaultResolvedType('config');
        $this->assertEquals("config('some_config')", $this->resolver->resolveParameter('some_config'));

        $this->resolver->setDefaultResolvedType('service');
        $this->assertEquals("service('some_service')", $this->resolver->resolveParameter('some_service'));
    }

    public function testCanRegisterAndUseAdditionalReferenceTypePlugins()
    {
        $reference = $this->getMock('BnpServiceDefinition\Reference\ReferenceInterface');

        $reference::staticExpects($this->any())
            ->method('getType')
            ->will($this->returnValue('some_type'));

        $reference->expects($this->any())
            ->method('compile')
            ->will($this->returnValue('true'));

        $this->resolver->setService(get_class($reference), $reference);
        $this->resolver->setAlias(call_user_func(array(get_class($reference), 'getType')), get_class($reference));

        $this->assertInstanceOf(get_class($reference), $this->resolver->get(get_class($reference)));
        $this->assertInstanceOf(
            get_class($reference),
            $this->resolver->get(call_user_func(array(get_class($reference), 'getType')))
        );

        $this->assertEquals(
            'true',
            $this->resolver->resolveParameter(array(
                'type' => call_user_func(array(get_class($reference), 'getType')),
                'value' => 'ignorable_value'
            ))
        );
    }
}