<?php

namespace BnpServiceDefinitionTest\Service;

use BnpServiceDefinition\Definition\DefinitionRepository;
use BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider;
use BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider;
use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Exception\RuntimeException;
use BnpServiceDefinition\Service\Evaluator;
use BnpServiceDefinition\Service\ParameterResolver;
use BnpServiceDefinitionTest\Factory\DefinitionAbstractFactoryTest;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceManager;

class EvaluatorTest extends DefinitionFactoryAbstractTest
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

    protected function createDefinitionWithName($name, DefinitionRepository $repository)
    {
        return $this->evaluator->evaluate($name, $repository);
    }

    protected function getServiceManager()
    {
        return $this->services;
    }
}
