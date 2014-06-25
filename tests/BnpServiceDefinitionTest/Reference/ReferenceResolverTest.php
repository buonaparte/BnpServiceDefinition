<?php

namespace BnpServiceDefinitionTest\Reference;

use BnpServiceDefinition\Reference\ConfigReference;
use BnpServiceDefinition\Reference\ReferenceResolver;
use BnpServiceDefinition\Reference\ServiceReference;
use BnpServiceDefinition\Reference\ValueReference;

class ReferenceResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ReferenceResolver
     */
    protected $resolver;

    protected function setUp()
    {
        $this->resolver = new ReferenceResolver();
    }

    public function testDefaultRegisteredPluginsAreAvailable()
    {
        $this->assertInstanceOf(
            'BnpServiceDefinition\Resolver\ConfigReference',
            $this->resolver->get(ConfigReference::getType())
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Resolver\ServiceReference',
            $this->resolver->get(ServiceReference::getType())
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Resolver\ValueReference',
            $this->resolver->get(ValueReference::getType())
        );
    }
}