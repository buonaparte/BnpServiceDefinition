<?php

namespace BnpServiceDefinitionTest\Reference;

use BnpServiceDefinition\Reference\ConfigReference;
use BnpServiceDefinition\Reference\Exception\InvalidArgumentException;

class ConfigReferenceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigReference
     */
    protected $configReference;

    protected function setUp()
    {
        $this->configReference = new ConfigReference();
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