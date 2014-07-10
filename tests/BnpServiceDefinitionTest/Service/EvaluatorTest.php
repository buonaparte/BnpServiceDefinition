<?php

namespace BnpServiceDefinitionTest\Service;

use BnpServiceDefinition\Definition\DefinitionRepository;
use BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider;
use BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider;
use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Exception\RuntimeException;
use BnpServiceDefinition\Service\Evaluator;
use BnpServiceDefinition\Service\ParameterResolver;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceManager;

class EvaluatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BnpServiceDefinition\Service\ParameterResolver
     */
    protected $parameterResolver;

    /**
     * @var ServiceManager
     */
    protected $services;

    /**
     * @var Language
     */
    protected $language;

    /**
     * @var Evaluator
     */
    protected $evaluator;

    protected function setUp()
    {
        $this->parameterResolver = new ParameterResolver();
        $this->services = new ServiceManager();

        $this->language = new Language();

        $extensions = array(new ConfigFunctionProvider(), new ServiceFunctionProvider());
        foreach ($extensions as $extension) {
            if ($extension instanceof ServiceLocatorAwareInterface) {
                $extension->setServiceLocator($this->services);
            }

            $this->language->registerExtension($extension);
        }

        $this->evaluator = new Evaluator($this->language, $this->parameterResolver);
    }

    public function testEvaluatedDefinitionArePulledFromRepository()
    {
        $repo = new DefinitionRepository(array(
            'a_service' => array(
                'class' => '\stdClass',
                'abstract' => true
            )
        ));

        $exceptionsThrown = 0;
        foreach (array('not_existing_service', 'a_service') as $definition) {
            try {
                $this->evaluator->evaluate($definition, $repo);
            } catch (RuntimeException $e) {
                $exceptionsThrown += 1;
            }
        }

        $this->assertEquals(2, $exceptionsThrown);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testEvaluatorChecksClassForClassExistence()
    {
        $repo = new DefinitionRepository(array(
            'a_service' => array(
                'class' => 'ClassDoesNotExists'
            )
        ));

        $this->evaluator->evaluate('a_service', $repo);
    }

    public function testEvaluatorSetsCustomErrorHandlerForInstantiatingAService()
    {
        $repo = new DefinitionRepository(array(
            'array' => array(
                'class' => '\ArrayObject',
                'arguments' => array(
                    array('type' => 'value', 'value' => array(1, 2, 3))
                )
            ),
            'evaluator_service' => array(
                'class' => 'BnpServiceDefinition\Service\Evaluator',
                'arguments' => array(
                    12,
                    5.4
                )
            )
        ));

        /** @var $arrayService \ArrayObject */
        $arrayService = $this->evaluator->evaluate('array', $repo);
        $this->assertEquals(array(1, 2, 3), $arrayService->getArrayCopy());

        $this->setExpectedException('BnpServiceDefinition\Exception\RuntimeException');
        $this->evaluator->evaluate('evaluator_service', $repo);
    }

    public function testEvaluatorChecksServiceMethodExistence()
    {
        $repo = new DefinitionRepository(array(
            'array' => array(
                'class' => '\ArrayObject',
                'calls' => array(
                    array('exchangeArray', array(array('type' => 'value', 'value' => array('elt'))))
                )
            ),
            'another_array' => array(
                'parent' => 'array',
                'calls' => array(
                    'methodDoesNotExist'
                )
            )
        ));

        /** @var $arrayService \ArrayObject */
        $arrayService = $this->evaluator->evaluate('array', $repo);
        $this->assertEquals(array('elt'), $arrayService->getArrayCopy());

        $this->setExpectedException('BnpServiceDefinition\Exception\RuntimeException');
        $this->evaluator->evaluate('another_array', $repo);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testEvaluatorSetsCustomErrorHandlerForMethodCalls()
    {
        $repo = new DefinitionRepository(array(
            'provider' => array(
                'class' => 'BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider',
                'calls' => array(
                    array('setServiceLocator', array(2.5))
                )
            ),
        ));

        $this->evaluator->evaluate('provider', $repo);
    }

    public function testEvaluatorSkipsMethodCallsWhichNotSatisfyConditions()
    {
        $repo = new DefinitionRepository(array(
            'array' => array(
                'class' => '\ArrayObject',
                'arguments' => array(
                    array('type' => 'value', 'value' => array(1, 2, 3))
                ),
                'calls' => array(
                    array(
                        'exchangeArray',
                        array(array('type' => 'value', 'value' => array('elt'))),
                        array(array('type' => 'value', 'value' => false))
                    )
                )
            ),
        ));

        /** @var $arrayService \ArrayObject */
        $arrayService = $this->evaluator->evaluate('array', $repo);
        $this->assertEquals(array(1, 2, 3), $arrayService->getArrayCopy());
    }

    public function testEvaluatorInjectsServiceValueInContextAfterInstantiationForConditions()
    {
        $repo = new DefinitionRepository(array(
            'array' => array(
                'class' => '\ArrayObject',
                'arguments' => array(
                    array('type' => 'value', 'value' => array(1, 2, 3))
                ),
                'calls' => array(
                    array(
                        'name' => 'exchangeArray',
                        'parameters' => array(array('type' => 'value', 'value' => array('elt'))),
                        'conditions' => array(array('type' => 'dsl', 'value' => '3 == service.count()'))
                    )
                )
            ),
        ));

        /** @var $arrayService \ArrayObject */
        $arrayService = $this->evaluator->evaluate('array', $repo);
        $this->assertEquals(array('elt'), $arrayService->getArrayCopy());
    }
}
