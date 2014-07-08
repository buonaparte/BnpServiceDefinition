<?php

namespace BnpServiceDefinitionTest\Definition;

use BnpServiceDefinition\Definition\ClassDefinition;
use BnpServiceDefinition\Definition\MethodCallDefinition;

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

    public function arraySpecsProvider()
    {
        return array(
            array(array(
                'class' => 'SomeClass',
                'args' => array('firstArgument', 'secondArgument')
            )),
            array(array(
                'class' => 'SomeClass',
                'arguments' => array('firstArgument', 'secondArgument')
            ))
        );
    }

    /**
     * @param array $arraySpecs
     * @dataProvider arraySpecsProvider
     */
    public function testInstanceFromArraySpecs(array $arraySpecs)
    {
        $definition = ClassDefinition::fromArray($arraySpecs);

        $this->assertEquals('SomeClass', $definition->getClass());
        $this->assertEquals(array('firstArgument', 'secondArgument'), $definition->getArguments());
    }

    public function testFromArrayIgnoresUndefinedProperties()
    {
        $definition = ClassDefinition::fromArray(array(
            'undefined_key' => 'undefined value'
        ));

        $this->assertInstanceOf('BnpServiceDefinition\Definition\ClassDefinition', $definition);
    }

    public function methodCallsArraySpecsProvider()
    {
        return array(
            array(array(
                'method_calls' => array(
                    array(
                        'name' => 'someSetter',
                        'params' => array('setterArg'),
                        'condition' => 'somethingIsTrue'
                    )
                )
            )),
            array(array(
                'calls' => array(
                    array(
                        'name' => 'someSetter',
                        'params' => array('setterArg'),
                        'condition' => 'somethingIsTrue'
                    )
                )
            )),
            array(array(
                'methodCalls' => array(
                    array(
                        'name' => 'someSetter',
                        'params' => array('setterArg'),
                        'condition' => 'somethingIsTrue'
                    )
                )
            ))
        );
    }

    /**
     * @param array $methodCallsArraySpecs
     * @dataProvider methodCallsArraySpecsProvider
     */
    public function testFromArrayCreatesMethodDefinitionsFromSpecs(array $methodCallsArraySpecs)
    {
        $definition = ClassDefinition::fromArray($methodCallsArraySpecs);

        $this->assertInstanceOf('BnpServiceDefinition\Definition\ClassDefinition', $definition);
        $this->assertInternalType('array', $definition->getMethodCalls());
        $this->assertNotEmpty($definition->getMethodCalls());

        $methodCalls = $definition->getMethodCalls();
        /** @var $methodCall MethodCallDefinition */
        $methodCall = $methodCalls[0];

        $this->assertInstanceOf('BnpServiceDefinition\Definition\MethodCallDefinition', $methodCall);
        $this->assertEquals('someSetter', $methodCall->getName());
        $this->assertEquals(array('setterArg'), $methodCall->getParameters());
        $this->assertEquals(array('somethingIsTrue'), $methodCall->getConditions());
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
        /** @var $firstCall MethodCallDefinition */
        $firstCall = $methodCalls[0];
        /** @var $secondCall MethodCallDefinition */
        $secondCall = $methodCalls[1];

        $this->assertInstanceOf('BnpServiceDefinition\Definition\MethodCallDefinition', $firstCall);
        $this->assertInstanceOf('BnpServiceDefinition\Definition\MethodCallDefinition', $secondCall);
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