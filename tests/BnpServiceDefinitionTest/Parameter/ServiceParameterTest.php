<?php

namespace BnpServiceDefinitionTest\Parameter;

use BnpServiceDefinition\Parameter\ServiceParameter;

class ServiceParameterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceParameter
     */
    protected $serviceReference;

    protected function setUp()
    {
        $this->serviceReference = new ServiceParameter();
    }

    public function testCompileCorrectProvidedString()
    {
        $definition = 'some_service';
        $unQuotedDefinition = "someone's_service";

        $this->assertEquals("service('some_service')", $this->serviceReference->compile($definition));
        $this->assertEquals("service('someone\\\'s_service')", $this->serviceReference->compile($unQuotedDefinition));
    }

    public function testWillThrowExceptionOnUnsupportedDefinition()
    {
        $definitions = array(1, 0.9, array('something'), new \stdClass());
        $exceptions = array();

        foreach ($definitions as $unSupportedDefinition) {
            try {
                $this->serviceReference->compile($unSupportedDefinition);
            } catch (\Exception $e) {
                $exceptions[] = $e;
            }
        }

        $this->assertEquals(count($definitions), count($exceptions));
        foreach ($exceptions as $e) {
            $this->assertInstanceOf('BnpServiceDefinition\Exception\InvalidArgumentException', $e);
        }
    }
}
