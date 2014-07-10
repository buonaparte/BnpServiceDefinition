<?php

namespace BnpServiceDefinitionTest\Dsl\Extension;

use BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider;
use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Exception\RuntimeException;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

class ServiceProviderTest extends \PHPUnit_Framework_TestCase
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
        $this->services = new ServiceManager();

        $this->services->setFactory(
            'ServiceFunctionProvider',
            function (ServiceLocatorInterface $services) {
                $provider = new ServiceFunctionProvider('ServiceFunctionProvider');
                $provider->setServiceLocator($services);

                return $provider;
            }
        );

        $this->language = new Language();
        $this->language->registerExtension('ServiceFunctionProvider');
        $this->language->setServiceLocator($this->services);
    }

    protected function overrideConfig(array $config = array())
    {
        $allowOverride = $this->services->getAllowOverride();

        $this->services->setAllowOverride(true);
        $configInstance = new Config($config);
        $configInstance->configureServiceManager($this->services);

        $this->services->setAllowOverride($allowOverride);
    }

    protected function getCompiledCode($part)
    {
        return sprintf('$this->services->get(\'ServiceFunctionProvider\')->getService(%s)', $part);
    }

    public function testCompiles()
    {
        $this->assertEquals(
            $this->getCompiledCode('"some_service", false, null'),
            $this->language->compile("service('some_service')")
        );
        $this->assertEquals(
            $this->getCompiledCode('"some_service", false, null'),
            $this->language->compile("service('some_service', FALSE)")
        );
        $this->assertEquals(
            $this->getCompiledCode('"some_service", true, null'),
            $this->language->compile("service('some_service', true)")
        );
        $this->assertEquals(
            $this->getCompiledCode('"some_service", true, "\\stdClass"'),
            $this->language->compile("service('some_service', true, '\stdClass')")
        );
    }

    public function testBasicEvaluation()
    {
        $this->overrideConfig(array(
            'services' => array(
                'a_service' => $service = new \stdClass()
            )
        ));

        $this->assertEquals(null, $this->language->evaluate("service('not_existing_service', true)"));
        $this->assertSame($service, $this->language->evaluate("service('a_service', false)"));
        $this->assertSame($service, $this->language->evaluate("service('a_service', false, '\stdClass')"));
    }

    public function testWillReThrowServiceManagerExceptionsDuringNonSilentRetrieval()
    {
        $this->overrideConfig(array(
            'factories' => array(
                'a_service' => function () {
                    throw new \RuntimeException();
                }
            )
        ));

        $passes = false;
        try {
            $this->language->evaluate("service('not_existing_service', false)");
        } catch (\Exception $e) {
            $this->assertInstanceOf('Zend\ServiceManager\Exception\ServiceNotFoundException', $e);
            $passes = true;
        }
        if (! $passes) {
            $this->fail('Expected exception not thrown');
        }

        try {
            $this->language->evaluate("service('a_service', false)");
        } catch (\Exception $e) {
            $this->assertInstanceOf('Zend\ServiceManager\Exception\ServiceNotCreatedException', $e);
            return;
        }

        $this->fail('Expected exception not thrown');
    }

    public function testWillThrowExceptionOnInvalidInstanceRetrieval()
    {
        $this->overrideConfig(array(
            'services' => array(
                'a' => new \stdClass(),
                'b' => new \ArrayObject(),
                'c' => 1,
                'd' => $this
            )
        ));

        $exceptionsThrown = 0;
        $aClass = str_replace('\\', '\\\\', get_class($this));
        foreach (array('a', 'b', 'c', 'd') as $existingService) {
            try {
                $this->language->evaluate("service('$existingService', false, '$aClass')");
            } catch (RuntimeException $e) {
                $exceptionsThrown += 1;
            }
        }

        $this->assertEquals(3, $exceptionsThrown);
    }
}
