<?php

namespace BnpServiceDefinitionTest\Definition;

use BnpServiceDefinition\Definition\ClassDefinition;
use BnpServiceDefinition\Definition\DefinitionRepository;
use BnpServiceDefinition\Definition\MethodDefinition;

class DefinitionRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testWithBasicDefinition()
    {
        $classDefinition = array(
            'class' => 'SomeClass',
            'arguments' => array('args'),
            'method_calls' => array(
                'aSetter'
            )
        );
        $repo = new DefinitionRepository(array(
            'definition' => $classDefinition
        ));

        $retrievedDefinition = $repo->getServiceDefinition('definition');
        $this->assertInstanceOf('BnpServiceDefinition\Definition\ClassDefinition', $retrievedDefinition);
        $this->assertEquals(ClassDefinition::fromArray($classDefinition), $retrievedDefinition);
    }

    public function testCanOverrideClassNameDefinitionsWithParent()
    {
        $repo = new DefinitionRepository(array(
            'first' => array(
                'class' => 'someClass',
                'arguments' => array('firstArg')
            ),
            'second' => array(
                'class' => 'anotherClass',
                'parent' => 'first'
            )
        ));

        $definition = $repo->getServiceDefinition('second');
        $this->assertInstanceOf('BnpServiceDefinition\Definition\ClassDefinition', $definition);
        $this->assertEquals('anotherClass', $definition->getClass());
        $this->assertEquals(array('firstArg'), $definition->getArguments());
    }

    public function testCanMergeConstructorArgumentsDefinitionsWithParent()
    {
        $repo = new DefinitionRepository(array(
            'first' => array(
                'class' => 'SomeClass',
                'arguments' => array('first')
            ),
            'second' => array(
                'parent' => 'first',
                'arguments' => array('second')
            ),
            'third' => array(
                'class' => 'SomeClass',
                'arguments' => array('#1' => 'first')
            ),
            'fourth' => array(
                'parent' => 'third',
                'arguments' => array('#1' => 'second')
            )
        ));

        $second = $repo->getServiceDefinition('second');
        $this->assertEquals('SomeClass', $second->getClass());
        $this->assertEquals(array('first', 'second'), $second->getArguments());

        $fourth = $repo->getServiceDefinition('fourth');
        $this->assertEquals('SomeClass', $fourth->getClass());
        $this->assertEquals(array('second'), array_values($fourth->getArguments()));
    }

    public function testCanMergeMethodCallParametersWithParent()
    {
        $repo = new DefinitionRepository(array(
            'first' => array(
                'class' => 'SomeClass',
                'method_calls' => array(
                    'firstMethod'
                )
            ),
            'second' => array(
                'parent' => 'first',
                'method_calls' => array(
                    array(
                        'name' => 'firstMethod',
                        'params' => array('firstParam')
                    )
                )
            ),
            'third' => array(
                'class' => 'SomeClass',
                'method_calls' => array(
                    array(
                        'name' => 'firstMethod',
                        'params' => array('#1' => 'firstParameter')
                    ),
                    array(
                        'name' => 'secondMethod',
                        'params' => array('firstParameter', 'secondParameter')
                    )
                )
            ),
            'fourth' => array(
                'parent' => 'third',
                'method_calls' => array(
                    array(
                        'name' => 'firstMethod',
                        'params' => array('#1' => 'secondParameter', '#2' => 'thirdParameter')
                    ),
                    array(
                        'name' => 'secondMethod',
                        'params' => array('thirdParameter')
                    ),
                    'thirdMethod'
                )
            )
        ));

        $second = $repo->getServiceDefinition('second');
        $fourth = $repo->getServiceDefinition('fourth');

        $secondMethodCalls = $second->getMethodCalls();
        $fourthMethodCalls = $fourth->getMethodCalls();

        $this->assertCount(1, $secondMethodCalls);
        $this->assertCount(3, $fourthMethodCalls);

        /** @var $secondMethodFirst MethodDefinition */
        $secondMethodFirst = $secondMethodCalls[0];
        /** @var $fourthMethodFirst MethodDefinition */
        $fourthMethodFirst = $fourthMethodCalls[0];
        /** @var $fourthMethodSecond MethodDefinition */
        $fourthMethodSecond = $fourthMethodCalls[1];
        /** @var $fourthMethodThird MethodDefinition */
        $fourthMethodThird = $fourthMethodCalls[2];

        $this->assertEquals('firstMethod', $secondMethodFirst->getName());
        $this->assertEquals(array('firstParam'), $secondMethodFirst->getParams());

        $this->assertEquals('firstMethod', $fourthMethodFirst->getName());
        $this->assertEquals(array('secondParameter', 'thirdParameter'), array_values($fourthMethodFirst->getParams()));

        $this->assertEquals('secondMethod', $fourthMethodSecond->getName());
        $this->assertEquals(
            array('firstParameter', 'secondParameter', 'thirdParameter'),
            array_values($fourthMethodSecond->getParams())
        );

        $this->assertEquals('thirdMethod', $fourthMethodThird->getName());
        $this->assertEmpty($fourthMethodThird->getParams());
    }

    public function testCanMergeMethodCallConditionWithParent()
    {
        $repo = new DefinitionRepository(array(
            'first' => array(
                'class' => 'SomeClass',
                'method_calls' => array(
                    'firstMethod'
                )
            ),
            'second' => array(
                'parent' => 'first',
                'method_calls' => array(
                    array(
                        'name' => 'firstMethod',
                        'condition' => 'somethingIsTrue'
                    )
                )
            ),
            'third' => array(
                'class' => 'SomeClass',
                'method_calls' => array(
                    array(
                        'name' => 'firstMethod',
                        'condition' => array('#1' => 'somethingIsTrue')
                    ),
                    array(
                        'name' => 'secondMethod',
                        'condition' => array('somethingIsTrue', 'somethingIsFalse')
                    )
                )
            ),
            'fourth' => array(
                'parent' => 'third',
                'method_calls' => array(
                    array(
                        'name' => 'firstMethod',
                        'condition' => array('#1' => 'somethingIsFalse', '#2' => 'somethingIsFalse')
                    ),
                    array(
                        'name' => 'secondMethod',
                        'condition' => array('unknown')
                    )
                )
            )
        ));

        $second = $repo->getServiceDefinition('second');
        $fourth = $repo->getServiceDefinition('fourth');

        $secondMethodCalls = $second->getMethodCalls();
        $fourthMethodCalls = $fourth->getMethodCalls();

        $this->assertCount(1, $secondMethodCalls);
        $this->assertCount(2, $fourthMethodCalls);

        /** @var $secondMethodFirst MethodDefinition */
        $secondMethodFirst = $secondMethodCalls[0];
        /** @var $fourthMethodFirst MethodDefinition */
        $fourthMethodFirst = $fourthMethodCalls[0];
        /** @var $fourthMethodSecond MethodDefinition */
        $fourthMethodSecond = $fourthMethodCalls[1];

        $this->assertEquals('firstMethod', $secondMethodFirst->getName());
        $this->assertEquals(array('somethingIsTrue'), $secondMethodFirst->getCondition());

        $this->assertEquals('firstMethod', $fourthMethodFirst->getName());
        $this->assertEquals(
            array('somethingIsFalse', 'somethingIsFalse'),
            array_values($fourthMethodFirst->getCondition())
        );

        $this->assertEquals('secondMethod', $fourthMethodSecond->getName());
        $this->assertEquals(
            array('somethingIsTrue', 'somethingIsFalse', 'unknown'),
            $fourthMethodSecond->getCondition()
        );
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWillThrowExceptionOnUndefinedResolvedClassNameDefinition()
    {
        $repo = new DefinitionRepository(array(
            'first' => array(
                'arguments' => array('first')
            ),
            'second' => array(
                'parent' => 'first',
                'arguments' => array('second')
            )
        ));

        $repo->getServiceDefinition('second');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWillThrowExceptionOnAbstractDefinitionRetrieval()
    {
        $repo = new DefinitionRepository(array(
            'first' => array(
                'class' => 'SomeClass',
                'abstract' => true,
            ),
            'second' => array(
                'parent' => 'first',
                'abstract' => true
            )
        ));

        $repo->getServiceDefinition('second');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWillThrowExceptionOnNotExistingDefinition()
    {
        $repo = new DefinitionRepository(array(
            'first' => array(
                'class' => '\stdClass'
            )
        ));

        $repo->getServiceDefinition('not_existing_class_def');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetDefinitionWillThrowExceptionOnCircularDependency()
    {
        $repo = new DefinitionRepository(array(
            'first' => array(
                'class' => 'stdClass',
                'arguments' => array(),
                'parent' => 'second'
            ),
            'second' => array(
                'class' => 'SomeClass',
                'parent' => 'first'
            )
        ));

        $repo->getServiceDefinition('first');
    }
}