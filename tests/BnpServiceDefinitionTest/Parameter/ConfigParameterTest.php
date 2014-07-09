<?php

namespace BnpServiceDefinitionTest\Parameter;

use BnpServiceDefinition\Parameter\ConfigParameter;
use BnpServiceDefinition\Exception\InvalidArgumentException;
use Zend\Stdlib\ArrayObject;

class ConfigParameterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigParameter
     */
    protected $configParameter;

    protected function setUp()
    {
        $this->configParameter = new ConfigParameter();
    }

    public function testCompileByProvidingValidArrayPathConfigPath()
    {
        $definition = array('path', 'to', 'a', 'nested_config', 'un \'quoted');

        $this->assertEquals(
            "config(['path', 'to', 'a', 'nested_config', 'un \\\'quoted'])",
            $this->configParameter->compile($definition)
        );
    }

    public function testCompileByProvidingATraversableInstanceAsPathConfig()
    {
        $definition = new ArrayObject(array('path', 'to', 'a', 'config'));

        $this->assertEquals("config(['path', 'to', 'a', 'config'])", $this->configParameter->compile($definition));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWillThrowExceptionWhenUnknownTypeProvided()
    {
        $definitions = array(1, 0.8);

        $failures = 0;
        foreach ($definitions as $invalidDefinition) {
            try {
                $this->configParameter->compile($invalidDefinition);
            } catch (\Exception $e) {
                $failures += 1;
            }
        }

        $this->assertEquals(2, $failures);

        $this->configParameter->compile(new \stdClass());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWillThrowExceptionWhenNotStringProvidedInArrayConfigPath()
    {
        $this->configParameter->compile(array('path', 'to', 'a', new \stdClass()));
    }
}
