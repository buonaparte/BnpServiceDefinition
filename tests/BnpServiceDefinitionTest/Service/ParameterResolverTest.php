<?php

namespace BnpServiceDefinitionTest\Service;

use BnpServiceDefinition\Service\ParameterResolver;

class ParameterResolverTest extends \PHPUnit_Framework_TestCase
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
            'BnpServiceDefinition\Parameter\ConfigParameter',
            $this->resolver->get('BnpServiceDefinition\Parameter\ConfigParameter')
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Parameter\ServiceParameter',
            $this->resolver->get('BnpServiceDefinition\Parameter\ServiceParameter')
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Parameter\ValueParameter',
            $this->resolver->get('BnpServiceDefinition\Parameter\ValueParameter')
        );
    }

    public function testDefaultRegisteredPluginsAreAvailableFromShortCutAliases()
    {
        $this->assertInstanceOf(
            'BnpServiceDefinition\Parameter\ConfigParameter',
            $this->resolver->get('config')
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Parameter\ServiceParameter',
            $this->resolver->get('service')
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Parameter\ValueParameter',
            $this->resolver->get('value')
        );
    }

    public function testDefaultParameterType()
    {
        $this->assertEquals('value', $this->resolver->getDefaultResolvedType());
        $this->assertEquals("'something'", $this->resolver->resolveParameter('something'));
    }

    public function testCanChangeFallbackParameterType()
    {
        $this->resolver->setDefaultResolvedType('value');
        $this->assertEquals('1', $this->resolver->resolveParameter(1));

        $this->resolver->setDefaultResolvedType('config');
        $this->assertEquals("config(['some_config'])", $this->resolver->resolveParameter('some_config'));

        $this->resolver->setDefaultResolvedType('service');
        $this->assertEquals("service('some_service')", $this->resolver->resolveParameter('some_service'));
    }

    public function testCanRegisterAndUseAdditionalParameterTypePlugins()
    {
        $reference = $this->getMock('BnpServiceDefinition\Parameter\ParameterInterface');

        $reference->expects($this->any())
            ->method('compile')
            ->will($this->returnValue('true'));

        $this->resolver->setService(get_class($reference), $reference);
        $this->resolver->setAlias('mock', get_class($reference));

        $this->assertInstanceOf(get_class($reference), $this->resolver->get(get_class($reference)));
        $this->assertInstanceOf(
            get_class($reference),
            $this->resolver->get('mock')
        );

        $this->assertEquals(
            'true',
            $this->resolver->resolveParameter(array(
                'type' => 'mock',
                'value' => 'ignorable_value'
            ))
        );
    }
}
