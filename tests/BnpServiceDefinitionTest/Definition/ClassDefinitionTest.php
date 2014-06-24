<?php

namespace BnpServiceDefinitionTest\Definition;

use BnpServiceDefinition\Definition\ClassDefinition;
use BnpServiceDefinition\Definition\MethodDefinition;

class ClassDefinitionTest extends \PHPUnit_Framework_TestCase
{
    public function testFromArrayDefinesDefaultValues()
    {
        $definition = ClassDefinition::fromArray(array());

        $this->assertInstanceOf('BnpServiceDefinition\Definition\ClassDefinition', $definition);
        $this->assertNull($definition->getClass());
        $this->assertInternalType('array', $definition->getArguments());
        $this->assertEmpty($definition->getArguments());
        $this->assertNull($definition->getParent());
        $this->assertInternalType('array', $definition->getMethodCalls());
        $this->assertEmpty($definition->getMethodCalls());
    }

    public function testFromArrayIgnoresUndefinedProperties()
    {
        $definition = ClassDefinition::fromArray(array(
            'undefined_key' => 'undefined value'
        ));

        $this->assertInstanceOf('BnpServiceDefinition\Definition\ClassDefinition', $definition);
    }

    public function testFromArrayCreatesMethodDefinitionsFromSpecs()
    {
        $definition = ClassDefinition::fromArray(array(
            'method_calls' => array(
                array(
                    'name' => 'someSetter',
                    'params' => array('setterArg'),
                    'condition' => 'somethingIsTrue'
                )
            )
        ));

        $this->assertInstanceOf('BnpServiceDefinition\Definition\ClassDefinition', $definition);
        $this->assertInternalType('array', $definition->getMethodCalls());
        $this->assertNotEmpty($definition->getMethodCalls());

        $methodCalls = $definition->getMethodCalls();
        /** @var $methodCall MethodDefinition */
        $methodCall = $methodCalls[0];

        $this->assertInstanceOf('BnpServiceDefinition\Definition\MethodDefinition', $methodCall);
        $this->assertEquals('someSetter', $methodCall->getName());
        $this->assertEquals(array('setterArg'), $methodCall->getParams());
        $this->assertEquals(array('somethingIsTrue'), $methodCall->getCondition());
    }

    public function testFromArrayCreatesMethodDefinitionsFromOnlyNameSpec()
    {
        $definition = ClassDefinition::fromArray(array(
            'method_calls' => array(
                'firstSetter',
                'secondSetter'
            )
        ));

        $methodCalls = $definition->getMethodCalls();
        /** @var $firstCall MethodDefinition */
        $firstCall = $methodCalls[0];
        /** @var $secondCall MethodDefinition */
        $secondCall = $methodCalls[1];

        $this->assertInstanceOf('BnpServiceDefinition\Definition\MethodDefinition', $firstCall);
        $this->assertInstanceOf('BnpServiceDefinition\Definition\MethodDefinition', $secondCall);
        $this->assertEquals('firstSetter', $firstCall->getName());
        $this->assertEquals('secondSetter', $secondCall->getName());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFromArrayThrowsExceptionInWrongMethodCallsSpecs()
    {
        ClassDefinition::fromArray(array(
            'method_calls' => array(
                array('params' => array(), 'condition' => array())
            )
        ));
    }
}