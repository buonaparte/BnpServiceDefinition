<?php

namespace BnpServiceDefinitionTest\Definition;

use BnpServiceDefinition\Definition\ClassDefinition;
use BnpServiceDefinition\Definition\DefinitionRepository;
use BnpServiceDefinition\Definition\MethodCallDefinition;
use BnpServiceDefinition\Exception\RuntimeException;

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

        /** @var $secondMethodFirst MethodCallDefinition */
        $secondMethodFirst = $secondMethodCalls[0];
        /** @var $fourthMethodFirst MethodCallDefinition */
        $fourthMethodFirst = $fourthMethodCalls[0];
        /** @var $fourthMethodSecond MethodCallDefinition */
        $fourthMethodSecond = $fourthMethodCalls[1];
        /** @var $fourthMethodThird MethodCallDefinition */
        $fourthMethodThird = $fourthMethodCalls[2];

        $this->assertEquals('firstMethod', $secondMethodFirst->getName());
        $this->assertEquals(array('firstParam'), $secondMethodFirst->getParameters());

        $this->assertEquals('firstMethod', $fourthMethodFirst->getName());
        $this->assertEquals(array('secondParameter', 'thirdParameter'), array_values($fourthMethodFirst->getParameters()));

        $this->assertEquals('secondMethod', $fourthMethodSecond->getName());
        $this->assertEquals(
            array('firstParameter', 'secondParameter', 'thirdParameter'),
            array_values($fourthMethodSecond->getParameters())
        );

        $this->assertEquals('thirdMethod', $fourthMethodThird->getName());
        $this->assertEmpty($fourthMethodThird->getParameters());
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

        /** @var $secondMethodFirst MethodCallDefinition */
        $secondMethodFirst = $secondMethodCalls[0];
        /** @var $fourthMethodFirst MethodCallDefinition */
        $fourthMethodFirst = $fourthMethodCalls[0];
        /** @var $fourthMethodSecond MethodCallDefinition */
        $fourthMethodSecond = $fourthMethodCalls[1];

        $this->assertEquals('firstMethod', $secondMethodFirst->getName());
        $this->assertEquals(array('somethingIsTrue'), $secondMethodFirst->getConditions());

        $this->assertEquals('firstMethod', $fourthMethodFirst->getName());
        $this->assertEquals(
            array('somethingIsFalse', 'somethingIsFalse'),
            array_values($fourthMethodFirst->getConditions())
        );

        $this->assertEquals('secondMethod', $fourthMethodSecond->getName());
        $this->assertEquals(
            array('somethingIsTrue', 'somethingIsFalse', 'unknown'),
            $fourthMethodSecond->getConditions()
        );
    }

    public function testGetTerminableDefinitionsWillOnlyReturnNotAbstractClassDefinitions()
    {
        $repo = new DefinitionRepository(array(
            'first' => array(
                'class' => 'some_class',
                'abstract' => true
            ),
            'second' => array(
                'parent' => 'first',
                'arguments' => array('firstArg'),
                'abstract' => true
            ),
            'third' => array(
                'parent' => 'second'
            ),
            'fourth' => array(
                'parent' => 'second',
                'arguments' => array('secondArg')
            )
        ));

        $this->assertInternalType('array', $repo->getTerminableDefinitions());
        $this->assertCount(2, $repo->getTerminableDefinitions());
        $this->assertEquals(array('third', 'fourth'), array_keys($repo->getTerminableDefinitions()));
    }

    public function testRepositoryAggregatesTerminableDefinitionsIterator()
    {
        $repo = new DefinitionRepository(array(
            'first' => array(
                'class' => 'some_class',
                'abstract' => true
            ),
            'second' => array(
                'parent' => 'first',
                'arguments' => array('firstArg'),
                'abstract' => true
            ),
            'third' => array(
                'parent' => 'second'
            ),
            'fourth' => array(
                'parent' => 'second',
                'arguments' => array('secondArg')
            )
        ));

        $this->assertInstanceOf('\IteratorAggregate', $repo);
        $this->assertEquals(new \ArrayIterator($repo->getTerminableDefinitions()), $repo->getIterator());
    }

    /**
     * @expectedException RuntimeException
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
     * @expectedException RuntimeException
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
     * @expectedException RuntimeException
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
     * @expectedException RuntimeException
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

    public function testComputedChecksumReflectsDefinitionSpecsChanges()
    {
        $firstDefinitionSpecs = array(
            'first' => array(
                'class' => 'stdClass'
            ),
            'second' => array(
                'class' => 'SomeClass'
            )
        );
        $secondDefinitionSpecs = array_reverse($firstDefinitionSpecs, true);

        $firstRepo = new DefinitionRepository($firstDefinitionSpecs);
        $secondRepo = new DefinitionRepository($secondDefinitionSpecs);

        $this->assertEquals(hash('md5', json_encode($firstDefinitionSpecs)), $firstRepo->getChecksum());
        $this->assertEquals(hash('md5', json_encode($secondDefinitionSpecs)), $secondRepo->getChecksum());

        $this->assertNotEquals($firstRepo->getChecksum(), $secondRepo->getChecksum());
    }

    public function definitionSpecsProvider()
    {
        return array(
            array(array(
                'first' => array(
                    'class' => 'SomeClass'
                ),
                'second' => array(
                    'class' => 'stdClass'
                )
            )),
            array(array(
                'first' => array(
                    'class' => 'SomeClass',
                    'abstract' => true
                ),
                'second' => array(
                    'parent' => 'first'
                )
            )),
            array(array(
                'first' => array(
                )
            ))
        );
    }

    /**
     * @param array $specs
     * @dataProvider definitionSpecsProvider
     */
    public function testHasDefinitionOperatesOnyOnSpecs(array $specs)
    {
        $repo = new DefinitionRepository($specs);

        foreach (array_keys($specs) as $existingDefinition) {
            $this->assertTrue($repo->hasDefinition($existingDefinition));
        }
    }
}
