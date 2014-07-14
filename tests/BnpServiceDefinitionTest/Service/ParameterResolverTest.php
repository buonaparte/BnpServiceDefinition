<?php

namespace BnpServiceDefinitionTest\Service;

use BnpServiceDefinition\Exception\InvalidArgumentException;
use BnpServiceDefinition\Service\ParameterResolver;
use Zend\ServiceManager\Exception\RuntimeException;

class ParameterResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BnpServiceDefinition\Service\ParameterResolver
     */
    protected $resolver;

    protected function setUp()
    {
        $this->resolver = new ParameterResolver();
    }

    public function testDefaultRegisteredPluginsAreAvailable()
    {
        $this->assertInstanceOf(
            'BnpServiceDefinition\Parameter\ConfigParameter',
            $this->resolver->get('BnpServiceDefinition\Parameter\ConfigParameter')
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Parameter\ServiceParameter',
            $this->resolver->get('BnpServiceDefinition\Parameter\ServiceParameter')
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Parameter\ValueParameter',
            $this->resolver->get('BnpServiceDefinition\Parameter\ValueParameter')
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Parameter\DslParameter',
            $this->resolver->get('BnpServiceDefinition\Parameter\DslParameter')
        );
    }

    public function testDefaultRegisteredPluginsAreAvailableFromShortCutAliases()
    {
        $this->assertInstanceOf(
            'BnpServiceDefinition\Parameter\ConfigParameter',
            $this->resolver->get('config')
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Parameter\ServiceParameter',
            $this->resolver->get('service')
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Parameter\ValueParameter',
            $this->resolver->get('value')
        );
        $this->assertInstanceOf(
            'BnpServiceDefinition\Parameter\DslParameter',
            $this->resolver->get('dsl')
        );
    }

    public function testDefaultParameterType()
    {
        $this->assertEquals('value', $this->resolver->getDefaultResolvedType());
        $this->assertEquals("'something'", $this->resolver->resolveParameter('something'));
    }

    public function invalidParameterProvider()
    {
        return array(
            array(array('type' => 'value')),
            array(array('value' => 'a_string')),
            array(array())
        );
    }

    public function parametersInOrderProvider()
    {
        return array(
            array(
                array(
                    array('type' => 'value', 'value' => 'a'),
                    array('type' => 'value', 'value' => 'b')
                )
            ),
            array(
                array(
                    array('type' => 'value', 'value' => 'a'),
                    array('type' => 'value', 'value' => 'b', 'order' => 1)
                )
            ),
            array(
                array(
                    array('type' => 'value', 'value' => 'a', 'order' => -1),
                    array('type' => 'value', 'value' => 'b')
                )
            ),
            array(
                array(
                    array('type' => 'value', 'value' => 'a', 'order' => 1),
                    array('type' => 'value', 'value' => 'b', 'order' => 2)
                )
            ),
            array(
                array(
                    array('type' => 'value', 'value' => 'b', 'order' => 1),
                    array('type' => 'value', 'value' => 'a')
                )
            ),
            array(
                array(
                    array('type' => 'value', 'value' => 'b'),
                    array('type' => 'value', 'value' => 'a', 'order' => -1)
                )
            ),
            array(
                array(
                    array('type' => 'value', 'value' => 'b', 'order' => -1),
                    array('type' => 'value', 'value' => 'a', 'order' => -2)
                )
            )
        );
    }

    /**
     * @param array $parameters
     * @dataProvider parametersInOrderProvider
     */
    public function testResolveParametersTakesOrderIntoAccount(array $parameters)
    {
        $parameters = $this->resolver->resolveParameters($parameters);
        $this->assertEquals(array("'a'", "'b'"), $parameters);
    }

    /**
     * @param $invalidParameter mixed
     * @dataProvider invalidParameterProvider
     * @expectedException InvalidArgumentException
     */
    public function testResolveParameterWillThrowExceptionOnInvalidParameterPassed($invalidParameter)
    {
        $this->resolver->resolveParameter($invalidParameter);
    }

    public function testCanChangeFallbackParameterType()
    {
        $this->resolver->setDefaultResolvedType('value');
        $this->assertEquals('1', $this->resolver->resolveParameter(1));

        $this->resolver->setDefaultResolvedType('config');
        $this->assertEquals("config(['some_config'])", $this->resolver->resolveParameter('some_config'));

        $this->resolver->setDefaultResolvedType('service');
        $this->assertEquals("service('some_service')", $this->resolver->resolveParameter('some_service'));
    }

    public function testCanRegisterAndUseAdditionalParameterTypePlugins()
    {
        $parameter = $this->getMock('BnpServiceDefinition\Parameter\ParameterInterface');

        $parameter->expects($this->any())
            ->method('compile')
            ->will($this->returnValue('true'));

        $this->resolver->setService(get_class($parameter), $parameter);
        $this->resolver->setAlias('mock', get_class($parameter));

        $this->assertInstanceOf(get_class($parameter), $this->resolver->get(get_class($parameter)));
        $this->assertInstanceOf(
            get_class($parameter),
            $this->resolver->get('mock')
        );

        $this->assertEquals(
            'true',
            $this->resolver->resolveParameter(array(
                'type' => 'mock',
                'value' => 'ignorable_value'
            ))
        );
    }

    /**
     * @expectedException RuntimeException
     */
    public function testPluginsGetValidatedUponRetrieval()
    {
        $this->resolver->setFactory(
            'invalid',
            function () {
                return new \stdClass();
            }
        );

        $this->resolver->resolveParameter(array(
            'type' => 'invalid',
            'value' => 'ignorable_value'
        ));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testWillThrowExceptionOnInvalidDefaultResolver()
    {
        $this->resolver->setDefaultResolvedType('unknown_type');
    }
}
