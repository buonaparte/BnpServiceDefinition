<?php

namespace BnpServiceDefinitionTest\Options;

use BnpServiceDefinition\Options\DefinitionOptions;

class DefinitionOptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanceDefaults()
    {
        $options = new DefinitionOptions();

        $this->assertFalse($options->getDumpFactories());
        $this->assertNull($options->getDumpDirectory());

        $this->assertInternalType('array', $options->getDefinitionAwareContainers());
        $this->assertEmpty($options->getDefinitionAwareContainers());
    }
}
