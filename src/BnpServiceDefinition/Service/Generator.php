<?php

namespace BnpServiceDefinition\Service;

use BnpServiceDefinition\Definition\ClassDefinition;
use BnpServiceDefinition\Definition\DefinitionRepository;
use BnpServiceDefinition\Definition\MethodDefinition;
use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Options\DefinitionOptions;
use BnpServiceDefinition\Service\ReferenceResolver;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

class Generator
{
    /**
     * @var DefinitionOptions
     */
    protected $options;

    /**
     * @var \BnpServiceDefinition\Service\ReferenceResolver
     */
    protected $referenceResolver;

    /**
     * @var Language
     */
    protected $language;

    /**
     * @var array
     */
    protected $definitionFactoryMethods = array();

    public function __construct(DefinitionOptions $options, ReferenceResolver $referenceResolver, Language $language)
    {
        $this->options = $options;
        $this->referenceResolver = $referenceResolver;
        $this->language = $language;
    }

    public function getGenerator(DefinitionRepository $repository, $filename = null)
    {
        $classFile = new FileGenerator();
        $classFile->setClass($this->generateAbstractFactoryClass($repository));

        if ($this->options->getDumpedAbstractFactoriesNamespace()) {
            $classFile->setNamespace($this->options->getDumpedAbstractFactoriesNamespace());
        }

        if (null !== $this->options->getDumpedAbstractFactoriesNamespace()) {
            $classFile->setNamespace($this->options->getDumpedAbstractFactoriesNamespace());
        }

        if (null !== $filename) {
            $classFile->setFilename($filename);
        }

        return $classFile;
    }

    protected function generateAbstractFactoryClass(DefinitionRepository $repository)
    {
        $class = ClassGenerator::fromArray(array(
            'name' => sprintf('BnpGeneratedAbstractFactory_%s', $repository->getChecksum()),
            'implemented_interfaces' => array(
                'Zend\ServiceManager\AbstractFactoryInterface'
            ),
            'properties' => array(
                PropertyGenerator::fromArray(array(
                    'name' => 'services',
                    'visibility' => 'protected',
                    'docblock' => array(
                        'tags' => array(
                            array(
                                'name' => 'var',
                                'content' => 'Zend\ServiceManager\ServiceLocatorInterface'
                            )
                        )
                    )
                ))
            ),
            'methods' => array(
                MethodGenerator::fromArray(array(
                    'name' => 'canCreateServiceWithName',
                    'parameters' => array(
                        ParameterGenerator::fromArray(array(
                            'name' => 'serviceLocator',
                            'type' => 'Zend\ServiceManager\ServiceLocatorInterface'
                        )),
                        'name',
                        'requestedName',
                    ),
                    'docblock' => array(
                        'short_description' => 'Determine if we can create a service with name',
                        'tags' => array(
                            new ParamTag(
                                'serviceLocatorInterface',
                                array('Zend\ServiceManager\ServiceLocatorInterface')
                            ),
                            new ParamTag(
                                'name',
                                array('string')
                            ),
                            new ParamTag(
                                'requestedName',
                                array('string')
                            ),
                            array(
                                'name' => 'return',
                                'content' => 'bool'
                            )
                        )
                    ),
                    'body' => $this->generateCanCreateMethodBody($repository)
                )),
                MethodGenerator::fromArray(array(
                    'name' => 'createServiceWithName',
                    'parameters' => array(
                        ParameterGenerator::fromArray(array(
                            'name' => 'serviceLocator',
                            'type' => 'Zend\ServiceManager\ServiceLocatorInterface'
                        )),
                        'name',
                        'requestedName',
                    ),
                    'docblock' => array(
                        'short_description' => 'Create service with name',
                        'tags' => array(
                            new ParamTag(
                                'serviceLocatorInterface',
                                array('Zend\ServiceManager\ServiceLocatorInterface')
                            ),
                            new ParamTag(
                                'name',
                                array('string')
                            ),
                            new ParamTag(
                                'requestedName',
                                array('string')
                            ),
                            array(
                                'name' => 'return',
                                'content' => 'mixed'
                            )
                        )
                    ),
                    'body' => $this->generateCreateMethodBody($repository)
                ))
            )
        ));

        $class->addMethods($this->definitionFactoryMethods);
        $this->definitionFactoryMethods = array();

        return $class;
    }

    protected function addDefinitionFactoryMethod(&$definitionName, ClassDefinition $definition)
    {
        $name = $definitionName;

        $definitionName = 'get' . ucfirst($this->getDefinitionCanonicalName($definitionName));
        $i = 0;
        while (array_key_exists($definitionName, $this->definitionFactoryMethods)) {
            $definitionName .= ++$i;
        }

        $this->definitionFactoryMethods[$definitionName] = MethodGenerator::fromArray(array(
            'name' => $definitionName,
            'visibility' => 'protected',
            'docblock' => array(
                'short_description' => sprintf('Returns the service registered under "%s" definition', $name),
                'tags' => array(
                    new ReturnTag('object')
                )
            ),
            'body' => $this->generateBodyForClassDefinition($definition)
        ));
    }

    protected function getDefinitionCanonicalName($name)
    {
        return preg_replace('@[^\w]@', '', $name);
    }

    /**
     * @param DefinitionRepository $repository
     * @return string
     */
    protected function generateCanCreateMethodBody(DefinitionRepository $repository)
    {
        return sprintf("\$this->services = \$serviceLocator;\n\nreturn in_array(\$name, array(%s));",
            implode(', ',
                array_map(
                    function ($definitionName) { return "'$definitionName'"; },
                    array_keys($repository->getTerminableDefinitions())
                )
            )
        );
    }

    /**
     * @param DefinitionRepository $repository
     * @return string
     */
    protected function generateCreateMethodBody(DefinitionRepository $repository)
    {
        if (! count($repository->getTerminableDefinitions())) {
            return '';
        }

        $casesBody = '';
        foreach ($repository as $name => $definition) {
            $methodName = $name;
            $this->addDefinitionFactoryMethod($methodName, $definition);

            $casesBody .= <<<CASE_BODY

    case '$name':
        return \$this->{$methodName}();
CASE_BODY;
        }

        return <<<SWITCH_STATEMENT_BODY
switch(\$requestedName) {
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

            if (null !== $methodCall->getCondition()) {
                $conditions = implode(
                    ' and ',
                    $this->referenceResolver->resolveReferences($methodCall->getCondition())
                );
                $methodCallBody .= <<<METHOD_CALL

if ({$this->compileDslPart($conditions, array('service'))}) {
    $methodCallBody
}
METHOD_CALL;
            }

            $body .= $methodCallBody . "\n";
        }

        return $body;
    }

    protected function compileDslPart($rawDsl, array $names = array())
    {
        return $this->language->compile($rawDsl, $names);
    }

    protected function compileReference($param, array $names = array())
    {
        return $this->compileDslPart($this->referenceResolver->resolveReference($param), $names);
    }

    protected function compileReferences(array $params = array(), $names = array())
    {
        $self = $this;
        return array_map(
            function ($param) use ($self, $names) { return $this->compileDslPart($param, $names); },
            $this->referenceResolver->resolveReferences($params)
        );
    }
}