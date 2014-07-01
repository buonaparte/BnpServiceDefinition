<?php

namespace BnpServiceDefinitionTest\Definition;

use BnpServiceDefinition\Definition\MethodCallDefinition;

class MethodDefinitionTest extends \PHPUnit_Framework_TestCase
{
    public function testWithDefaults()
    {
        $definition = new MethodCallDefinition('setSomething');

        $this->assertInternalType('array', $definition->getParams());
        $this->assertEmpty($definition->getParams());
        $this->assertNull($definition->getConditions());
    }

    public function testSingleConditionGetsRepresentedByArray()
    {
        $definition = new MethodCallDefinition('setSomething', array(), 'somethingIsTrue');

        $this->assertInternalType('array', $definition->getConditions());
        $this->assertNotEmpty($definition->getConditions());

        $conditions = $definition->getConditions();
        $this->assertEquals('somethingIsTrue', $conditions[0]);
    }

    public function testInstanceFromArraySpecs()
    {
        $definition = MethodCallDefinition::fromArray(array(
            'name' => 'someSetter',
            'params' => array('setterArg'),
            'condition' => 'somethingIsFalse'
        ));

        $this->assertInstanceOf('BnpServiceDefinition\Definition\MethodDefinition', $definition);
        $this->assertEquals('someSetter', $definition->getName());
        $this->assertEquals(array('setterArg'), $definition->getParams());
        $this->assertEquals(array('somethingIsFalse'), $definition->getConditions());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFromArrayMethodThrowsExceptionOnNoName()
    {
        MethodCallDefinition::fromArray(array(
            'params' => array()
        ));
    }
}