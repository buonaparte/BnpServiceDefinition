<?php

namespace BnpServiceDefinitionTest\Factory;

use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Factory\LanguageFactory;
use Zend\ServiceManager\ServiceManager;

class LanguageFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    protected function setUp()
    {
        $this->services = new ServiceManager();
        $this->services->setFactory('language', new LanguageFactory());
    }

    /**
     * @return Language
     */
    protected function getLanguage()
    {
        return $this->services->get('language');
    }

    public function testBasicInstantiation()
    {
        $language = $this->getLanguage();
    }
}
