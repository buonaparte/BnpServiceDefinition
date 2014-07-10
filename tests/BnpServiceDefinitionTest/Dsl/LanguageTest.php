<?php

namespace BnpServiceDefinitionTest\Dsl;

use BnpServiceDefinition\Dsl\Language;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayObject;

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

    protected function setUp()
    {
        $this->services = new ServiceManager(new Config(array(
            'services' => array(
                'Config' => array(
                )
            ),
        )));

        $this->language = new Language();
        $this->language->setServiceLocator($this->services);
    }

    public function testExtensionsGetRegisteredBeforeFirstEvaluateCall()
    {
        $first = $this->getMock('BnpServiceDefinition\Dsl\Extension\Feature\ContextVariablesProviderInterface');
        $second = $this->getMock('BnpServiceDefinition\Dsl\Extension\Feature\ContextVariablesProviderInterface');

        $first->expects($this->once())
            ->method('getContextVariables')
            ->will($this->returnValue(array()));

        $second->expects($this->exactly(0))
            ->method('getContextVariables');

        $this->language->registerExtension($first);

        $this->language->evaluate('true');

        $this->language->registerExtension($second);
    }

    public function testExtensionsGetRegisteredBeforeFirstCompileCall()
    {
        $first = $this->getMock('BnpServiceDefinition\Dsl\Extension\Feature\FunctionProviderInterface');
        $second = $this->getMock('BnpServiceDefinition\Dsl\Extension\Feature\FunctionProviderInterface');

        $first->expects($this->atLeastOnce())
            ->method('getName')
            ->will($this->returnValue('some_function'));
        $first->expects($this->atLeastOnce())
            ->method('getEvaluator')
            ->will($this->returnValue(
                function () {
                }
            ));
        $first->expects($this->atLeastOnce())
            ->method('getCompiler')
            ->will($this->returnValue(
                function () {
                    return '';
                }
            ));

        $second->expects($this->exactly(0))
            ->method('getName')
            ->will($this->returnValue('some_other_function'));
        $second->expects($this->exactly(0))
            ->method('getEvaluator')
            ->will($this->returnValue(
                function () {
                }
            ));
        $second->expects($this->exactly(0))
            ->method('getCompiler')
            ->will($this->returnValue(
                function () {
                    return '';
                }
            ));

        $this->language->registerExtension($first);

        $this->language->compile('true');

        $this->language->registerExtension($second);
    }

    public function getCompositeProvidersMocks()
    {
        $extension = $this->getMock('BnpServiceDefinitionTest\Dsl\Extension\Feature\CompositeProviderMock');

        $extension->expects($this->atLeastOnce())
            ->method('getContextVariables')
            ->will($this->returnValue(array()));

        $extension->expects($this->atLeastOnce())
            ->method('getName')
            ->will($this->returnValue('some_function'));
        $extension->expects($this->atLeastOnce())
            ->method('getEvaluator')
            ->will($this->returnValue(
                function () {
                }
            ));
        $extension->expects($this->atLeastOnce())
            ->method('getCompiler')
            ->will($this->returnValue(
                function () {
                    return '';
                }
            ));

        return array(
            array($extension)
        );
    }

    /**
     * @dataProvider getCompositeProvidersMocks
     */
    public function testRegistersCompositeProviderExtensions($extension)
    {
        $this->language->registerExtension($extension);

        $this->language->evaluate('true');
    }

    /**
     * @dataProvider getCompositeProvidersMocks
     */
    public function testRegistersExtensionProviderFromInjectedServiceLocator($extension)
    {
        $this->services->setService('some_extension', $extension);
        $this->language->registerExtension('some_extension');

        $this->language->evaluate('true');
    }

    public function testSilentPassesInvalidExtensions()
    {
        $this->services->setFactory(
            'failing_service',
            function () {
                throw new \RuntimeException();
            }
        );

        $this->language->registerExtension(1);
        $this->language->registerExtension(2.1);
        $this->language->registerExtension(array('something'));
        $this->language->registerExtension(new \stdClass());
        $this->language->registerExtension('not_existing_service_extension');
        $this->language->registerExtension('failing_service');

        $invalidEvaluator = $this->getMock('BnpServiceDefinition\Dsl\Extension\Feature\FunctionProviderInterface');
        $invalidEvaluator->expects($this->any())
            ->method('getEvaluator')
            ->will($this->returnValue(null));
        $invalidEvaluator->expects($this->any())
            ->method('getCompiler')
            ->will($this->returnValue(
                function () {
                }
            ));
        $this->language->registerExtension($invalidEvaluator);

        $invalidCompiler = $this->getMock('BnpServiceDefinition\Dsl\Extension\Feature\FunctionProviderInterface');
        $invalidCompiler->expects($this->any())
            ->method('getEvaluator')
            ->will($this->returnValue(
                function () {
                }
            ));
        $invalidCompiler->expects($this->any())
            ->method('getCompiler')
            ->will($this->returnValue('not_a_callable'));
        $this->language->registerExtension($invalidCompiler);

        $this->language->evaluate('true');
    }

    public function testContextVariablesProviderCanReturnATraversableInstance()
    {
        $extension = $this->getMock('BnpServiceDefinition\Dsl\Extension\Feature\ContextVariablesProviderInterface');
        $extension->expects($this->any())
            ->method('getContextVariables')
            ->will($this->returnValue(new ArrayObject(array('a' => 2.3))));

        $this->language->registerExtension($extension);

        $this->assertEquals('2.3', $this->language->evaluate('a'));
    }

    public function testContextVariablesProviderThatReturnContextWithNumericKeysAreIgnored()
    {
        $first = $this->getMock('BnpServiceDefinition\Dsl\Extension\Feature\ContextVariablesProviderInterface');
        $first->expects($this->any())
            ->method('getContextVariables')
            ->will($this->returnValue(new ArrayObject(array('a' => 2.3, '10' => 'something'))));

        $second = $this->getMock('BnpServiceDefinition\Dsl\Extension\Feature\ContextVariablesProviderInterface');
        $second->expects($this->any())
            ->method('getContextVariables')
            ->will($this->returnValue(array('a' => 5, 1 => 'somethingElse')));

        $this->language->registerExtension($first);
        $this->language->registerExtension($second);

        $exceptionsThrown = 0;
        foreach (array('a', 'a + 1') as $expression) {
            try {
                $this->language->evaluate($expression);
            } catch (\Exception $e) {
                $exceptionsThrown += 1;
            }
        }

        $this->assertEquals(2, $exceptionsThrown);
    }

    public function testContextVariablesProviderResultsAreMerged()
    {
        $first = $this->getMock('BnpServiceDefinition\Dsl\Extension\Feature\ContextVariablesProviderInterface');
        $first->expects($this->any())
            ->method('getContextVariables')
            ->will($this->returnValue(new ArrayObject(array('a' => 2.3))));

        $second = $this->getMock('BnpServiceDefinition\Dsl\Extension\Feature\ContextVariablesProviderInterface');
        $second->expects($this->any())
            ->method('getContextVariables')
            ->will($this->returnValue(array('a' => 5)));

        $this->language->registerExtension($first);
        $this->language->registerExtension($second);

        $this->assertEquals('5', $this->language->evaluate('a'));
    }
}
