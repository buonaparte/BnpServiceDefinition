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
            'first' => array(
                'abstract' => true,
                'class' => array('type' => 'service', 'value' => 'something_from_a_service'),
                'arguments' => array('firstParameter', 'secondParameter'),
                'method_calls' => array(
                    array(
                        'name' => 'setSomething',
                        'params' => array(array('type' => 'config', 'value' => 'some:nested:config'))
                    ),
                    array(
                        'name' => 'setSomethingElse',
                        'params' => array('somethingElse')
                    )
                )
            ),
            'second' => array(
                'abstract' => 'true',
                'parent' => 'first',
                'class' => array('type' => 'service', 'value' => 'something_from_a_service'),
                'arguments' => array('firstParameter', 'secondParameter'),
                'method_calls' => array(
                    array(
                        'name' => 'setSomething',
                        'params' => array(array('type' => 'config', 'value' => 'some:nested:config'))
                    ),
                    array(
                        'name' => 'setSomethingElse',
                        'params' => array('somethingElse'),
                        'condition' => array(
                            array('type' => 'config', 'value' => 'some:variable:from:nested:config'),
                        )
                    )
                )
            ),
            'second#' => array(
                'parent' => 'second',
                'class' => array('type' => 'service', 'value' => 'something_from_a_service'),
                'arguments' => array('firstParameter', 'secondParameter'),
                'method_calls' => array(
                    array(
                        'name' => 'setSomething',
                        'params' => array(array('type' => 'config', 'value' => 'some:nested:config'))
                    ),
                    array(
                        'name' => 'setSomethingElse',
                        'params' => array('somethingElse'),
                        'condition' => array(
                            array('type' => 'config', 'value' => 'some:variable:from:nested:config'),
                        )
                    )
                )
            )
        )));

        $this->assertTrue(true);
    }
}