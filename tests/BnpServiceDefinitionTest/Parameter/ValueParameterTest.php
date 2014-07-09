<?php

namespace BnpServiceDefinitionTest\Parameter;

use BnpServiceDefinition\Parameter\ValueParameter;

class ValueParameterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BnpServiceDefinition\Parameter\ValueParameter
     */
    protected $valueReference;

    protected function setUp()
    {
        $this->valueReference = new ValueParameter();
    }

    public function testCompilesWithPrimitives()
    {
        $this->assertEquals('4', $this->valueReference->compile(4));
        $this->assertEquals('4.7', $this->valueReference->compile(4.7));
        $this->assertEquals("'something'", $this->valueReference->compile('something'));
        $this->assertEquals("'\\\'quoted\\\''", $this->valueReference->compile("'quoted'"));
    }

    public function testCompilesNestedArrays()
    {
        $definition = array(1, 0.9, "'quoted'", array('something'));

        $this->assertEquals("[1, 0.9, '\\\'quoted\\\'', ['something']]", $this->valueReference->compile($definition));
    }

    public function testCompilesNestedHashTables()
    {
        $definition = array('a' => 'something', "'b" => array(1.00, 2), 'c' => array('foo' => 'bar'));

        $this->assertEquals(
            "{'a': 'something', '\\\'b': [1.0, 2], 'c': {'foo': 'bar'}}",
            $this->valueReference->compile($definition)
        );
    }
}
