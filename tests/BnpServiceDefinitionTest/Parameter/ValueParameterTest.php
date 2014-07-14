<?php

namespace BnpServiceDefinitionTest\Parameter;

use BnpServiceDefinition\Exception\InvalidArgumentException;
use BnpServiceDefinition\Parameter\ValueParameter;

class ValueParameterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BnpServiceDefinition\Parameter\ValueParameter
     */
    protected $valueParameter;

    protected function setUp()
    {
        $this->valueParameter = new ValueParameter();
    }

    public function testCompilesWithPrimitives()
    {
        $this->assertEquals('4', $this->valueParameter->compile(4));
        $this->assertEquals('4.7', $this->valueParameter->compile(4.7));
        $this->assertEquals("'something'", $this->valueParameter->compile('something'));
        $this->assertEquals("'\\\'quoted\\\''", $this->valueParameter->compile("'quoted'"));
        $this->assertEquals('true', $this->valueParameter->compile(true));
        $this->assertEquals('false', $this->valueParameter->compile(false));
        $this->assertEquals('null', $this->valueParameter->compile(null));
    }

    public function testCompilesNestedArrays()
    {
        $definition = array(1, 0.9, "'quoted'", array('something'));

        $this->assertEquals("[1, 0.9, '\\\'quoted\\\'', ['something']]", $this->valueParameter->compile($definition));
    }

    public function testCompilesNestedHashTables()
    {
        $definition = array('a' => 'something', "'b" => array(1.00, 2), 'c' => array('foo' => 'bar'));

        $this->assertEquals(
            "{'a': 'something', '\\\'b': [1.0, 2], 'c': {'foo': 'bar'}}",
            $this->valueParameter->compile($definition)
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCompileWillThrowExceptionUponNonSupportedValues()
    {
        $this->valueParameter->compile(new \stdClass());
    }
}
