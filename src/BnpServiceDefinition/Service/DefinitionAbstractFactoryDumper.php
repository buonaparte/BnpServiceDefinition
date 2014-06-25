<?php

namespace BnpServiceDefinition\Service;

use BnpServiceDefinition\Definition\ClassDefinition;
use BnpServiceDefinition\Definition\DefinitionRepository;
use BnpServiceDefinition\Definition\MethodDefinition;
use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Options\DefinitionOptions;
use BnpServiceDefinition\Reference\ReferenceResolver;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\PropertyGenerator;

class DefinitionAbstractFactoryDumper
{
    /**
     * @var DefinitionOptions
     */
    protected $options;

    /**
     * @var \BnpServiceDefinition\Reference\ReferenceResolver
     */
    protected $referenceResolver;

    /**
     * @var Language
     */
    protected $language;

    public function __construct(DefinitionOptions $options, ReferenceResolver $referenceResolver, Language $language)
    {
        $this->options = $options;
        $this->referenceResolver = $referenceResolver;
        $this->language = $language;
    }

    public function dump(DefinitionRepository $repository, $file = null)
    {
        $data = $this->generateAbstractFactoryClass($repository);
        if (null !== $file) {
            file_put_contents($file, $data);
        }

        return $data;
    }

    protected function generateAbstractFactoryClass(DefinitionRepository $repository)
    {
        return ClassGenerator::fromArray(array(
            'name' => sprintf('BnpGeneratedAbstractFactory_%s', $repository->getChecksum()),
            'namespace' => $this->options->getDumpedAbstractFactoriesNamespace(),
            'properties' => array(
                PropertyGenerator::fromArray(array(
                    'name' => 'services',
                    'visibility' => 'protected'
                ))
            ),
            'methods' => array(
                MethodGenerator::fromArray(array(
                    'name' => 'canCreateServiceWithName',
                    'parameters' => array(
                        array('name' => 'serviceLocator'),
                        array('name' => 'name'),
                        array('name' => 'requestedName')
                    ),
                    'body' => $this->generateCanCreateMethodBody($repository)
                )),
                MethodGenerator::fromArray(array(
                    'name' => 'createServiceWithName',
                    'parameters' => array(
                        array('name' => 'serviceLocator'),
                        array('name' => 'name'),
                        array('name' => 'requestedName')
                    ),
                    'body' => $this->generateCreateMethodBody($repository)
                ))
            )
        ))->generate();
    }

    /**
     * @param DefinitionRepository $repository
     * @return string
     */
    protected function generateCanCreateMethodBody(DefinitionRepository $repository)
    {
        return sprintf('return in_array($name, array(%s));',
            implode(', ',
                array_map(
                    function ($definitionName) { return "'$definitionName'"; },
                    $repository->getDefinitions())
            )
        );
    }

    /**
     * @param DefinitionRepository $repository
     * @return string
     */
    protected function generateCreateMethodBody(DefinitionRepository $repository)
    {
        $casesBody = '';
        foreach ($repository->getDefinitions() as $definition) {
            $casesBody .= <<<CASE_BODY

    case '$definition':
        {$this->generateBodyForClassDefinition($repository->getServiceDefinition($definition))}
CASE_BODY;
        }
        return <<<SWITCH_STATEMENT_BODY
switch(\$name) {
    $casesBody
}
SWITCH_STATEMENT_BODY;
    }

    protected function generateBodyForClassDefinition(ClassDefinition $definition)
    {
        $compiledArguments = implode(',', $this->compileReferences($definition->getArguments()));
        return <<<STATEMENT_BODY
\$serviceClassName = {$this->compileReference($definition->getClass())};
\$service = new \$serviceClassName($compiledArguments);
{$this->generateBodyForMethodCallsDefinition($definition->getMethodCalls())}

return \$service;
STATEMENT_BODY;

    }

    protected function generateBodyForMethodCallsDefinition($methodCalls)
    {
        $body = '';
        foreach ($methodCalls as $methodCall) {
            /** @var $methodCall MethodDefinition */
            $compiledParams = implode(', ', $this->compileReferences($methodCall->getParams()));
            $methodCallBody = <<<METHOD_CALL_BODY
\$service->{$methodCall->getName()}($compiledParams);
METHOD_CALL_BODY;


            if (null === $methodCall->getCondition()) {
                $conditions = implode(' and ', $this->referenceResolver->resolveReferences($methodCall->getCondition()));
                $body .= <<<METHOD_CALL

if ({$this->compileDslPart($conditions)}) {
    $methodCallBody
}
METHOD_CALL;

            }
        }

        return $body;
    }

    protected function compileDslPart($rawDsl, $names = array())
    {
        return $this->language->compile($rawDsl, $names);
    }

    protected function compileReference($param)
    {
        return $this->compileDslPart($this->referenceResolver->resolveReference($param));
    }

    protected function compileReferences(array $params = array())
    {
        $self = $this;
        return array_map(
            function ($param) use ($self) { return $this->compileDslPart($param); },
            $this->referenceResolver->resolveReferences($params)
        );
    }
}