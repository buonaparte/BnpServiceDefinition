<?php

namespace BnpServiceDefinitionTest\Dsl;

use BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider;
use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Options\DefinitionOptions;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

class LanguageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    /**
     * @var Language
     */
    protected $language;

    /**
     * @var DefinitionOptions
     */
    protected $definitionOptions;

    protected function setUp()
    {
        $this->services = new ServiceManager(new Config(array(
            'services' => array(
                'Config' => array(
                )
            ),
        )));

        $definitions = $this->definitionOptions = new DefinitionOptions(array());
        $this->services->setFactory(
            'ConfigFunctionProvider',
            function (ServiceLocatorInterface $services) use ($definitions) {
                return new ConfigFunctionProvider('ConfigFunctionProvider', $definitions, $services);
            });


        $this->language = new Language();
        $this->language->registerExtension('ConfigFunctionProvider');
        $this->language->setServiceManager($this->services);
    }

    protected function overrideConfig(array $config = array())
    {
        $allowOverride = $this->services->getAllowOverride();

        $this->services->setAllowOverride(true);
        $this->services->setService('Config', $config);

        $this->services->setAllowOverride($allowOverride);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testEvaluationWithoutConfig()
    {
        $this->assertNull($this->language->evaluate('config("not_existing_key")'));
        $this->assertNull($this->language->evaluate('config("not_existing_key", true)'));
        $this->assertNull($this->language->evaluate('config("not_existing_key", true, "array")'));

        $this->language->evaluate('config("not_existing_key", false)');
    }

    public function testEvaluationWithBasicConfig()
    {
        $this->overrideConfig(array(
            'key1' => array('key2' => 'value1')
        ));

        $this->assertNotNull($this->language->evaluate('config("key1")'));
        $this->assertInternalType('array', $this->language->evaluate('config("key1")'));
        $this->assertArrayHasKey('key2', $this->language->evaluate('config("key1")'));

        $config = $this->language->evaluate('config("key1")');
        $this->assertEquals('value1', $config['key2']);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testEvaluationWithNestedConfig()
    {
        $this->overrideConfig(array(
            'key1' => array(
                'key2' => array(
                    'key3' => 'value'
                )
            )
        ));

        $this->assertNotNull($this->language->evaluate('config("key1")'));

        $this->assertNull($this->language->evaluate('config("key1:key3")'));
        $this->assertNull($this->language->evaluate('config("key1.key3")'));

        $this->assertInternalType('array', $this->language->evaluate('config("key1:key2")'));
        $this->assertEquals('value', $this->language->evaluate('config("key1:key2:key3")'));
        $this->assertEquals('value', $this->language->evaluate('config("key1:key2:key3", false, "string")'));

        $this->assertEquals('value', $this->language->evaluate('config("key1:key2:key3", false, "int")'));
    }
}