<?php

namespace BnpServiceDefinitionTest\Parameter;

use BnpServiceDefinition\Parameter\ConfigParameter;
use BnpServiceDefinition\Exception\InvalidArgumentException;

class ConfigParameterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigParameter
     */
    protected $configReference;

    protected function setUp()
    {
        $this->configReference = new ConfigParameter();
    }

    public function testCompileByProvidingStringConfigPath()
    {
        $definition = 'path:to:a:nested_config';
        $unQuotedDefinition = "path:to:a:nested's_config";

        $this->assertEquals("config('path:to:a:nested_config')", $this->configReference->compile($definition));
        $this->assertEquals(
            "config('path:to:a:nested\\'s_config')",
            $this->configReference->compile($unQuotedDefinition)
        );
    }

    public function testCompileByProvidingValidArrayPathConfigPath()
    {
        $definition = array('path', 'to', 'a', 'nested_config', 'un \'quoted');

        $this->assertEquals(
            "config(['path', 'to', 'a', 'nested_config', 'un \\'quoted'])",
            $this->configReference->compile($definition)
        );
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
                $this->configReference->compile($invalidDefinition);
            } catch (\Exception $e) {
                $failures += 1;
            }
        }

        $this->assertEquals(2, $failures);

        $this->configReference->compile(new \stdClass());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWillThrowExceptionWhenNotStringProvidedInArrayConfigPath()
    {
        $this->configReference->compile(array('path', 'to', 'a', new \stdClass()));
    }
}