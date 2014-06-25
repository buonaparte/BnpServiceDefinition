<?php

namespace BnpServiceDefinitionTest\Service;

use BnpServiceDefinition\Reference\ConfigReference;
use BnpServiceDefinition\Service\ReferenceResolver;
use BnpServiceDefinition\Reference\ServiceReference;
use BnpServiceDefinition\Reference\ValueReference;

class ReferenceResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BnpServiceDefinition\Service\ReferenceResolver
     */
    protected $resolver;

    protected function setUp()
    {
        $this->resolver = new ReferenceResolver();
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
            $this->resolver->get(ConfigReference::getType())
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Reference\ServiceReference',
            $this->resolver->get(ServiceReference::getType())
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Reference\ValueReference',
            $this->resolver->get(ValueReference::getType())
        );
    }

    public function testDefaultReferenceType()
    {
        $this->assertEquals('value', $this->resolver->getDefaultResolvedType());
        $this->assertEquals("'something'", $this->resolver->resolveReference('something'));
    }

    public function testCanChangeFallbackReferenceType()
    {
        $this->resolver->setDefaultResolvedType('value');
        $this->assertTrue('1', $this->resolver->resolveReference(1));

        $this->resolver->setDefaultResolvedType('config');
        $this->assertTrue("config('some_config')", $this->resolver->resolveReference('some_config'));

        $this->resolver->setDefaultResolvedType('service');
        $this->assertTrue("service('some_service')", $this->resolver->resolveReference('some_service'));
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
            $this->resolver->resolveReference(array(
                'type' => call_user_func(array(get_class($reference), 'getType')),
                'value' => 'ignorable_value'
            ))
        );
    }
}