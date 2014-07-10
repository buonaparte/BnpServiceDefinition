<?php

namespace BnpServiceDefinitionTest\Service;

use BnpServiceDefinition\Definition\DefinitionRepository;
use BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider;
use BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider;
use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Options\DefinitionOptions;
use BnpServiceDefinition\Service\Generator;
use BnpServiceDefinition\Service\ParameterResolver;
use Zend\Code\Generator\MethodGenerator;
use Zend\ServiceManager\ServiceManager;

class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefinitionOptions
     */
    protected $options;

    /**
     * @var \BnpServiceDefinition\Service\ParameterResolver
     */
    protected $parameterResolver;

    /**
     * @var ServiceManager
     */
    protected $services;

    /**
     * @var Language
     */
    protected $language;

    /**
     * @var Generator
     */
    protected $generator;

    /**
     * @var int
     */
    protected $immutableGeneratedFactoryMethodsCount;

    protected function setUp()
    {
        $this->options = new DefinitionOptions();
        $this->parameterResolver = new ParameterResolver();
        $this->services = new ServiceManager();

        $this->language = new Language();
        $this->language->registerExtension(new ConfigFunctionProvider());
        $this->language->registerExtension(new ServiceFunctionProvider());

        $this->generator = new Generator($this->language, $this->parameterResolver, $this->options);

        $boot = $this->generator->generate('SampleClassName', new DefinitionRepository(array()));
        $this->immutableGeneratedFactoryMethodsCount = count($boot->getClass()->getMethods());
    }

    public function testCanGenerateEmptyDefinitions()
    {
        $out = $this->generator->generate('SampleClassName', new DefinitionRepository(array()));

        $this->assertInstanceOf('Zend\Code\Generator\FileGenerator', $out);
        $this->assertCount(1, $out->getClasses());
        $this->assertEquals('SampleClassName', $out->getClass()->getName());
        $this->assertNull($out->getFilename());
    }

    public function testAddsFilenameIfSpecified()
    {
        $out = $this->generator->generate('SampleClassName', new DefinitionRepository(array()), 'a_file.php');

        $this->assertInstanceOf('Zend\Code\Generator\FileGenerator', $out);
        $this->assertEquals('a_file.php', $out->getFilename());
    }

    public function testCanGenerateForDefinitionsWithSameCanonicalNameWithoutCollision()
    {
        $out = $this->generator->generate(
            'SampleClassName',
            new DefinitionRepository($definitions = array(
                'A\Service' => array(
                    'class' => '\stdClass'
                ),
                'A\\Service' => array(
                    'class' => '\ArrayObject'
                ),
                'ServiceLocator' => array(
                    'class' => '\stdClass'
                )
            ))
        );
        $nameCanonical = 'AService';

        $getMethodName = function (MethodGenerator $method) {
            return $method->getName();
        };

        $this->assertInstanceOf('Zend\Code\Generator\FileGenerator', $out);
        $this->assertCount(
            $this->immutableGeneratedFactoryMethodsCount + count(array_keys($definitions)),
            $out->getClass()->getMethods()
        );

        $this->assertContains("get$nameCanonical", array_map($getMethodName, $out->getClass()->getMethods()));
        $this->assertContains('getServiceLocator1', array_map($getMethodName, $out->getClass()->getMethods()));
        for ($i=1; $i<count(array_keys($definitions)) - 1; $i++) {
            $this->assertContains("get$nameCanonical$i", array_map($getMethodName, $out->getClass()->getMethods()));
        }
    }

    public function testGeneratesComplexDefinitions()
    {
        $out = $this->generator->generate(
            'SomeClassName',
            new DefinitionRepository(array(
                'a_service' => array(
                    'class' => '\ArrayObject',
                    'arguments' => array(
                        array('type' => 'value', 'value' => array())
                    ),
                    'calls' => array(
                        array(
                            'name' => 'exchangeArray',
                            'parameters' => array(
                                array('type' => 'value', 'value' => array('firstItem'))
                            )
                        ),
                        array(
                            'name' => 'exchangeArray',
                            'parameters' => array(
                                array('type' => 'value', 'value' => array('firstItem', 'secondItem'))
                            ),
                            'conditions' => array(
                                array('type' => 'dsl', 'value' => '0 == service.count()')
                            )
                        )
                    )
                )
            ))
        );

        $this->assertInstanceOf('Zend\Code\Generator\FileGenerator', $out);
        $this->assertCount($this->immutableGeneratedFactoryMethodsCount + 1, $out->getClass()->getMethods());
    }

    public function testGeneratorSkipsNonTerminalDefinitions()
    {
        $out = $this->generator->generate(
            'SampleClassName',
            new DefinitionRepository($definitions = array(
                'first' => array(
                    'class' => '\stdClass'
                ),
                'second' => array(
                    'class' => '\ArrayObject'
                ),
                'third' => array(
                    'abstract' => true,
                    'class' => '\stdClass'
                )
            ))
        );

        $this->assertInstanceOf('Zend\Code\Generator\FileGenerator', $out);
        $this->assertCount($this->immutableGeneratedFactoryMethodsCount + 2, $out->getClass()->getMethods());
    }
}
