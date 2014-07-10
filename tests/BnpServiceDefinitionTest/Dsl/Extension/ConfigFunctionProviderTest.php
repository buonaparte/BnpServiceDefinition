<?php

namespace BnpServiceDefinitionTest\Dsl\Extension;

use BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider;
use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Exception\InvalidArgumentException;
use BnpServiceDefinition\Exception\RuntimeException;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

class ConfigFunctionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    /**
     * @var Language
     */
    protected $language;

    protected function setUp()
    {
        $this->services = new ServiceManager(new Config(array(
            'services' => array(
                'Config' => array(
                )
            ),
        )));

        $this->services->setFactory(
            'ConfigFunctionProvider',
            function (ServiceLocatorInterface $services) {
                $provider = new ConfigFunctionProvider('ConfigFunctionProvider');
                $provider->setServiceLocator($services);

                return $provider;
            }
        );

        $this->language = new Language();
        $this->language->registerExtension('ConfigFunctionProvider');
        $this->language->setServiceLocator($this->services);
    }

    protected function overrideConfig(array $config = array())
    {
        $allowOverride = $this->services->getAllowOverride();

        $this->services->setAllowOverride(true);
        $this->services->setService('Config', $config);

        $this->services->setAllowOverride($allowOverride);
    }

    protected function getCompiledCode($part)
    {
        return sprintf('$this->services->get(\'ConfigFunctionProvider\')->getConfigValue(%s)', $part);
    }

    public function testCompilesStringPath()
    {
        $this->assertEquals(
            $this->getCompiledCode('"some_key", true, null'),
            $this->language->compile("config('some_key')")
        );
        $this->assertEquals(
            $this->getCompiledCode('"some_key", false, null'),
            $this->language->compile("config('some_key', FALSE)")
        );
        $this->assertEquals(
            $this->getCompiledCode('"some_key", false, "int"'),
            $this->language->compile("config('some_key', false, 'int')")
        );
    }

    public function testCompilesArrayPath()
    {
        $this->assertEquals(
            $this->getCompiledCode('array(0 => "some_key"), true, null'),
            $this->language->compile("config(['some_key'])")
        );
        $this->assertEquals(
            $this->getCompiledCode('array(0 => "some_key", 1 => "tail"), false, null'),
            $this->language->compile("config(['some_key', 'tail'], false)")
        );
        $this->assertEquals(
            $this->getCompiledCode('array(0 => "some_key", 1 => "escaped\'key"), false, "int"'),
            $this->language->compile("config(['some_key', 'escaped\\'key'], false, 'int')")
        );
    }

    public function testCompilesNestedConfigDefinitions()
    {
        $this->assertEquals(
            $this->getCompiledCode(sprintf('%s, true, null', $this->getCompiledCode('"some_key", true, null'))),
            $this->language->compile("config(config('some_key'))")
        );
        $this->assertEquals(
            $this->getCompiledCode(sprintf(
                'array(0 => %s, 1 => "sub_key"), true, null',
                $this->getCompiledCode('"some_key", false, "string"')
            )),
            $this->language->compile("config([config('some_key', false, 'string'), 'sub_key'])")
        );
    }

    /**
     * @expectedException RuntimeException
     */
    public function testEvaluationWithoutConfig()
    {
        $this->assertNull($this->language->evaluate("config('not_existing_key')"));
        $this->assertNull($this->language->evaluate("config('not_existing_key', true)"));
        $this->assertNull($this->language->evaluate("config('not_existing_key', true, 'array')"));

        $this->language->evaluate("config('not_existing_key', false)");
    }

    public function testEvaluationWithBasicConfig()
    {
        $this->overrideConfig(array(
            'key1' => array('key2' => 'value1')
        ));

        $this->assertNotNull($this->language->evaluate("config('key1')"));
        $this->assertInternalType('array', $this->language->evaluate("config('key1')"));
        $this->assertArrayHasKey('key2', $this->language->evaluate("config('key1')"));

        $config = $this->language->evaluate("config('key1')");
        $this->assertEquals('value1', $config['key2']);
    }

    public function testEvaluationWithArrayPathNestedConfig()
    {
        $this->overrideConfig(array(
            'key1' => array(
                'key2' => array(
                    'key3' => 'value'
                )
            )
        ));

        $this->assertNotNull($this->language->evaluate("config(['key1'])"));

        $this->assertNull($this->language->evaluate("config(['key1', 'key3'])"));
        $this->assertNull($this->language->evaluate("config(['key1.key3'])"));

        $this->assertInternalType('array', $this->language->evaluate("config(['key1', 'key2'])"));
        $this->assertEquals('value', $this->language->evaluate("config(['key1', 'key2', 'key3'])"));
        $this->assertEquals('value', $this->language->evaluate("config(['key1', 'key2', 'key3'], false, 'string')"));
    }

    public function testWillThrowExceptionOnNonSilentTypeSpecification()
    {
        $this->overrideConfig(array(
            'key1' => 2.5,
            'key2' => 'a value',
            'key3' => 4,
            'key4' => true
        ));

        $tests = array('key1' => 'boolean', 'key2' => 'double', 'key3' => 'string', 'key4' => 'array');
        $exceptionsThrown = 0;
        foreach ($tests as $config => $type) {
            try {
                $this->language->evaluate("config('{$config}', false, '{$type}')");
            } catch (RuntimeException $e) {
                $exceptionsThrown += 1;
            }
        }

        $this->assertEquals(count($tests), $exceptionsThrown);
    }

    public function testEvaluatesNestedConfigDefinitions()
    {
        $this->overrideConfig(array(
            'key1' => array(
                'key2' => array(
                    'key3' => 'value'
                ),
            ),
            'key4' => 'key2',
            'key5' => array('key1', 'key2', 'key3')
        ));

        $this->assertEquals('value', $this->language->evaluate("config(config('key5'))"));
        $this->assertEquals(array('key3' => 'value'), $this->language->evaluate("config(['key1', config('key4')])"));
    }

    public function invalidConfigArgumentProvider()
    {
        return array(
            array("config(2.3)"),
            array("config(true)"),
            array("config(5)"),
            array("config([])"),
            array("config(['config', []])")
        );
    }

    /**
     * @param $invalidArgument string
     * @dataProvider invalidConfigArgumentProvider
     * @expectedException InvalidArgumentException
     */
    public function testEvaluationWillThrowAnExceptionUponInvalidConfigArgumentProvided($invalidArgument)
    {
        $this->language->evaluate($invalidArgument);
    }
}
