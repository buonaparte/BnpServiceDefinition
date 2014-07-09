<?php

namespace BnpServiceDefinitionTest\Definition;

use BnpServiceDefinition\Definition\MethodCallDefinition;
use BnpServiceDefinition\Exception\InvalidArgumentException;

class MethodDefinitionCallTest extends \PHPUnit_Framework_TestCase
{
    public function testWithDefaults()
    {
        $definition = new MethodCallDefinition('setSomething');

        $this->assertInternalType('array', $definition->getParameters());
        $this->assertEmpty($definition->getParameters());
        $this->assertFalse($definition->hasConditions());
        $this->assertNull($definition->getConditions());
    }

    public function testSingleConditionGetsRepresentedByArray()
    {
        $definition = new MethodCallDefinition('setSomething', array(), 'somethingIsTrue');

        $this->assertInternalType('array', $definition->getConditions());
        $this->assertTrue($definition->hasConditions());
        $this->assertNotEmpty($definition->getConditions());

        $conditions = $definition->getConditions();
        $this->assertEquals('somethingIsTrue', $conditions[0]);
    }

    public function arraySpecsProvider()
    {
        return array(
            array(array(
                'name' => 'someSetter',
                'params' => array('setterArg'),
                'condition' => 'somethingIsFalse'
            )),
            array(array(
                'name' => 'someSetter',
                'parameters' => array('setterArg'),
                'condition' => 'somethingIsFalse'
            )),
            array(array(
                'name' => 'someSetter',
                'params' => array('setterArg'),
                'conditions' => 'somethingIsFalse'
            ))
        );
    }

    /**
     * @param array $arraySpecs
     * @dataProvider arraySpecsProvider
     */
    public function testInstanceFromArraySpecs(array $arraySpecs)
    {
        $definition = MethodCallDefinition::fromArray($arraySpecs);

        $this->assertInstanceOf('BnpServiceDefinition\Definition\MethodCallDefinition', $definition);
        $this->assertEquals('someSetter', $definition->getName());
        $this->assertEquals(array('setterArg'), $definition->getParameters());
        $this->assertEquals(array('somethingIsFalse'), $definition->getConditions());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFromArrayMethodThrowsExceptionOnNoName()
    {
        MethodCallDefinition::fromArray(array(
            'params' => array()
        ));
    }
}
