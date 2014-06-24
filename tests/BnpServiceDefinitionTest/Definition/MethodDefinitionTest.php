<?php

namespace BnpServiceDefinitionTest\Definition;

use BnpServiceDefinition\Definition\MethodDefinition;

class MethodDefinitionTest extends \PHPUnit_Framework_TestCase
{
    public function testWithDefaults()
    {
        $definition = new MethodDefinition('setSomething');

        $this->assertInternalType('array', $definition->getParams());
        $this->assertEmpty($definition->getParams());
        $this->assertNull($definition->getCondition());
    }

    public function testSingleConditionGetsRepresentedByArray()
    {
        $definition = new MethodDefinition('setSomething', array(), 'somethingIsTrue');

        $this->assertInternalType('array', $definition->getCondition());
        $this->assertNotEmpty($definition->getCondition());

        $conditions = $definition->getCondition();
        $this->assertEquals('somethingIsTrue', $conditions[0]);
    }

    public function testInstanceFromArraySpecs()
    {
        $definition = MethodDefinition::fromArray(array(
            'name' => 'someSetter',
            'params' => array('setterArg'),
            'condition' => 'somethingIsFalse'
        ));

        $this->assertInstanceOf('BnpServiceDefinition\Definition\MethodDefinition', $definition);
        $this->assertEquals('someSetter', $definition->getName());
        $this->assertEquals(array('setterArg'), $definition->getParams());
        $this->assertEquals(array('somethingIsFalse'), $definition->getCondition());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFromArrayMethodThrowsExceptionOnNoName()
    {
        MethodDefinition::fromArray(array(
            'params' => array()
        ));
    }
}