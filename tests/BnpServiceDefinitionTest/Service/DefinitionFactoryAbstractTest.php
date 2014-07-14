<?php

namespace BnpServiceDefinitionTest\Service;

use BnpServiceDefinition\Definition\DefinitionRepository;
use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Service\ParameterResolver;
use Zend\ServiceManager\ServiceManager;

abstract class DefinitionFactoryAbstractTest extends \PHPUnit_Framework_TestCase
{
    abstract protected function createDefinitionWithName($name, DefinitionRepository $repository);

    /**
     * @return ServiceManager
     */
    abstract protected function getServiceManager();

    /**
     * @expectedException \RuntimeException
     */
    public function testFactoryChecksClassForClassExistence()
    {
        $repo = new DefinitionRepository(array(
            'a_service' => array(
                'class' => 'ClassDoesNotExists'
            )
        ));

        $this->createDefinitionWithName('a_service', $repo);
    }

    public function testFactorySetsCustomErrorHandlerForInstantiatingAService()
    {
        $repo = new DefinitionRepository(array(
            'array' => array(
                'class' => '\ArrayObject',
                'arguments' => array(
                    array('type' => 'value', 'value' => array(1, 2, 3))
                )
            ),
            'evaluator_service' => array(
                'class' => 'BnpServiceDefinition\Service\Factory',
                'arguments' => array(
                    12,
                    5.4
                )
            )
        ));

        /** @var $arrayService \ArrayObject */
        $arrayService = $this->createDefinitionWithName('array', $repo);
        $this->assertEquals(array(1, 2, 3), $arrayService->getArrayCopy());

        $this->setExpectedException('\RuntimeException');
        $this->createDefinitionWithName('evaluator_service', $repo);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWillThrowExceptionOnNonStringMethodCallNameProvided()
    {
        $repo = new DefinitionRepository(array(
            'array' => array(
                'class' => '\ArrayObject',
                'calls' => array(
                    array(
                        'name' => array('type' => 'value', 'value' => array('InvalidMethodCall'))
                    )
                )
            ),
        ));

        $this->createDefinitionWithName('array', $repo);
    }

    public function testFactoryChecksServiceMethodExistence()
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
        $arrayService = $this->createDefinitionWithName('array', $repo);
        $this->assertEquals(array('elt'), $arrayService->getArrayCopy());

        $this->setExpectedException('\RuntimeException');
        $this->createDefinitionWithName('another_array', $repo);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFactorySetsCustomErrorHandlerForMethodCalls()
    {
        $repo = new DefinitionRepository(array(
            'provider' => array(
                'class' => 'BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider',
                'calls' => array(
                    array('setServiceLocator', array(2.5))
                )
            ),
        ));

        $this->createDefinitionWithName('provider', $repo);
    }

    public function testFactorySkipsMethodCallsWhichNotSatisfyConditions()
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
        $arrayService = $this->createDefinitionWithName('array', $repo);
        $this->assertEquals(array(1, 2, 3), $arrayService->getArrayCopy());
    }

    public function testFactoryInjectsServiceValueInContextAfterInstantiationForConditions()
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
        $arrayService = $this->createDefinitionWithName('array', $repo);
        $this->assertEquals(array('elt'), $arrayService->getArrayCopy());
    }

    public function setterInjectionWithOrderProvider()
    {
        return array(
            array(
                array(
                    'evaluator' => array(
                        'class' => 'BnpServiceDefinition\Service\Evaluator',
                        'arguments' => array(
                            array('type' => 'service', 'value' => 'language'),
                            array('type' => 'service', 'value' => 'parameter_resolver')
                        )
                    )
                ),
                true
            ),
            array(
                array(
                    'evaluator' => array(
                        'class' => 'BnpServiceDefinition\Service\Evaluator',
                        'arguments' => array(
                            array('type' => 'service', 'value' => 'language', 'order' => -1),
                            array('type' => 'service', 'value' => 'parameter_resolver')
                        )
                    )
                ),
                true
            ),
            array(
                array(
                    'evaluator' => array(
                        'class' => 'BnpServiceDefinition\Service\Evaluator',
                        'arguments' => array(
                            array('type' => 'service', 'value' => 'language'),
                            array('type' => 'service', 'value' => 'parameter_resolver', 'order' => 1)
                        )
                    )
                ),
                true
            ),
            array(
                array(
                    'evaluator' => array(
                        'class' => 'BnpServiceDefinition\Service\Evaluator',
                        'arguments' => array(
                            array('type' => 'service', 'value' => 'language', 'order' => -1),
                            array('type' => 'service', 'value' => 'parameter_resolver', 'order' => 1)
                        )
                    )
                ),
                true
            ),
            array(
                array(
                    'evaluator' => array(
                        'class' => 'BnpServiceDefinition\Service\Evaluator',
                        'arguments' => array(
                            array('type' => 'service', 'value' => 'language'),
                            array('type' => 'service', 'value' => 'parameter_resolver', 'order' => -1)
                        )
                    )
                ),
                false
            ),
            array(
                array(
                    'evaluator' => array(
                        'class' => 'BnpServiceDefinition\Service\Evaluator',
                        'arguments' => array(
                            array('type' => 'service', 'value' => 'language', 'order' => -1),
                            array('type' => 'service', 'value' => 'parameter_resolver', 'order' => -2)
                        )
                    )
                ),
                false
            ),
        );
    }

    /**
     * @param array $definitions
     * @param $valid
     * @dataProvider setterInjectionWithOrderProvider
     */
    public function testSetterInjectionPassesArgumentsInOrder(array $definitions, $valid)
    {
        $repo = new DefinitionRepository($definitions);

        $services = $this->getServiceManager();
        $services->setService('language', new Language());
        $services->setService('parameter_resolver', new ParameterResolver());

        if ($valid) {
            $evaluator = $this->createDefinitionWithName('evaluator', $repo);
            $this->assertInstanceOf('BnpServiceDefinition\Service\Evaluator', $evaluator);
        } else {
            $this->setExpectedException('\RuntimeException');
            $this->createDefinitionWithName('evaluator', $repo);
        }
    }
}
