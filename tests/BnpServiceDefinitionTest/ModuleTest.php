<?php

namespace BnpServiceDefinitionTest;

use BnpServiceDefinition\Module;
use Zend\EventManager\EventManager;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\Request;
use Zend\Stdlib\Response;

class ModuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Module
     */
    protected $module;

    protected function setUp()
    {
        $this->module = new Module();
    }

    public function testAutoloaderConfigReturnsModuleNamespace()
    {
        $namespace = str_replace('Test', '', __NAMESPACE__);

        $config = $this->module->getAutoloaderConfig();
        $this->assertArrayHasKey('Zend\Loader\StandardAutoloader', $config);

        $std = $config['Zend\Loader\StandardAutoloader'];
        $this->assertArrayHasKey('namespaces', $std);

        $namespaces = $std['namespaces'];
        $this->assertArrayHasKey($namespace, $namespaces);
        $this->assertTrue(is_dir($namespaces[$namespace]));
    }

    public function testConfigReturnsArrayOrTraversableInstance()
    {
        $config = $this->module->getConfig();

        if (is_object($config)) {
            $this->assertInstanceOf('\Traversable', $config);
        } else {
            $this->assertInternalType('array', $config);
        }
    }

    public function testWillPullInitializeAndAttachAbstractFactoryOnBootstrapEvent()
    {
        $services = new ServiceManager();

        $factory = $this->getMock('BnpServiceDefinitionTest\Mock\InitializableAbstractFactory');

        $factory->expects($this->once())
            ->method('init');

        $factory->expects($this->once())
            ->method('canCreateServiceWithName')
            ->with($services, 'service', 'service')
            ->will($this->returnValue(false));

        $services->setService('BnpServiceDefinition\Factory\DefinitionAbstractFactory', $factory);
        $services->setService('Request', new Request());
        $services->setService('Response', new Response());

        $events = new EventManager();
        $services->setService('EventManager', $events);
        $events->attach(MvcEvent::EVENT_BOOTSTRAP, array($this->module, 'onBootstrap'));

        $event = new MvcEvent(MvcEvent::EVENT_BOOTSTRAP);
        $event->setApplication(new Application(array(), $services));

        $events->trigger($event);
        $this->assertFalse($services->has('service'));
    }
}
