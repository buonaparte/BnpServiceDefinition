<?php

namespace BnpServiceDefinitionTest\Service;

use BnpServiceDefinition\Definition\DefinitionRepository;
use BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider;
use BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider;
use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Options\DefinitionOptions;
use BnpServiceDefinition\Service\Generator;
use BnpServiceDefinition\Service\ParameterResolver;
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
    protected $referenceResolver;

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

    protected function setUp()
    {
        $this->options = new DefinitionOptions();
        $this->referenceResolver = new ParameterResolver();
        $this->services = new ServiceManager();

        $this->language = new Language();
        $this->language->registerExtension(new ConfigFunctionProvider(
            $this->options
        ));
        $this->language->registerExtension(new ServiceFunctionProvider(
        ));

        $this->generator = new Generator($this->language, $this->referenceResolver, $this->options);
    }

    public function testCanGenerateEmptyDefinitions()
    {
        $this->generator->generate(new DefinitionRepository(array(
        )));

        $this->assertTrue(true);
    }
}