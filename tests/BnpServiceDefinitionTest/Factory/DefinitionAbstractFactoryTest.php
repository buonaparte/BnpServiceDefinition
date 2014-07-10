<?php

namespace BnpServiceDefinitionTest\Factory;

use Zend\ServiceManager\ServiceManager;

class DefinitionAbstractFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    protected function setUp()
    {
        $this->services = new ServiceManager();
    }

    public function testSetup()
    {
        $this->assertTrue(true);
    }
}
