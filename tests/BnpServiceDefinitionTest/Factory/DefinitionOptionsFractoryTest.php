<?php

namespace BnpServiceDefinitionTest\Factory;

use BnpServiceDefinition\Factory\DefinitionOptionsFactory;
use BnpServiceDefinition\Options\DefinitionOptions;
use Zend\ServiceManager\ServiceManager;

class DefinitionOptionsFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    protected function setUp()
    {
        $this->services = new ServiceManager();
        $this->services->setService('Config', array());
        $this->services->setFactory('options', new DefinitionOptionsFactory());
    }

    protected function overrideConfig(array $config)
    {
        $oldOverride = $this->services->getAllowOverride();

        $this->services->setAllowOverride(true);
        $this->services->setService('Config', $config);

        $this->services->setAllowOverride($oldOverride);
    }

    /**
     * @return DefinitionOptions
     */
    protected function getOptions()
    {
        return $this->services->get('options');
    }

    public function testInstanceWithoutSpecs()
    {
        $options = $this->getOptions();
        $this->assertInstanceOf('BnpServiceDefinition\Options\DefinitionOptions', $options);
    }

    public function testInstanceWithSpecs()
    {
        $this->overrideConfig(array(
            'bnp-service-definition' => array(
                'dump_factories' => true,
                'dump_directory' => './a/directory',
                'definition_aware_containers' => array(
                    'MyContainer' => 'my_config'
                )
            )
        ));

        $options = $this->getOptions();

        $this->assertTrue($options->getDumpFactories());
        $this->assertEquals('./a/directory', $options->getDumpDirectory());
        $this->assertEquals(
            array('MyContainer' => 'my_config'),
            $options->getDefinitionAwareContainers()
        );
    }
}
