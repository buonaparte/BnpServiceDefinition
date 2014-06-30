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
    protected $immutableRegisteredMethods = array('canCreateServiceWithName', 'createServiceWithName');

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
                    'body' => $this->getCanCreateMethodBody($repository)
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
                    'body' => $this->getCreateMethodBody($repository)
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
        while (
            array_key_exists($definitionName, $this->immutableRegisteredMethods)
            ||
            array_key_exists($definitionName, $this->definitionFactoryMethods)
        ) {
            $definitionName .= ++$i;
        }

        $this->definitionFactoryMethods[$definitionName] = MethodGenerator::fromArray(array(
            'name' => $definitionName,
            'parameters' => array(
                ParameterGenerator::fromArray(array(
                    'name' => 'definitionName',
                    'type' => 'string'
                ))
            ),
            'visibility' => 'protected',
            'docblock' => array(
                'short_description' => sprintf('Returns the service registered under "%s" definition', $name),
                'tags' => array(
                    new ParamTag(
                        'name',
                        array('string')
                    ),
                    new ReturnTag('object')
                )
            ),
            'body' => $this->getFactoryMethodBody($definition)
        ));
    }

    protected function getDefinitionCanonicalName($name)
    {
        return preg_replace('@[^\w]@', '', $name);
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

    /**
     * @param DefinitionRepository $repository
     * @return string
     */
    protected function getCanCreateMethodBody(DefinitionRepository $repository)
    {
        $knownDefinitions = implode(
            ', ',
            array_map(
                function ($definitionName) { return "'$definitionName'"; },
                array_keys($repository->getTerminableDefinitions())
            )
        );

        return
<<<TEMPLATE
\$this->services = \$serviceLocator;
return in_array(\$requestedName, array($knownDefinitions));
TEMPLATE;
    }

    protected function getCreateMethodBody(DefinitionRepository $repository)
    {
        if (! count($repository->getTerminableDefinitions())) {
            return '';
        }

        $cases = '';
        foreach ($repository as $name => $definition) {
            $canonicalName = $name;
            $this->addDefinitionFactoryMethod($canonicalName, $definition);

            $cases .= "\n" . $this->getCaseStatementBody($name, $canonicalName);
        }

        return
<<<TEMPLATE
    switch (\$requestedName) {
        $cases
    }
TEMPLATE;
    }

    protected function getCaseStatementBody($name, $methodName)
    {
        return
<<<TEMPLATE
    case '$name':
        return \$this->$methodName('$name');
TEMPLATE;
    }

    protected function getFactoryMethodBody(ClassDefinition $definition)
    {
        $methodCalls = '';
        foreach (array_values($definition->getMethodCalls()) as $i => $methodCall) {
            /** @var $methodCall MethodDefinition */
            $methodCalls .= "\n" . $this->getFactoryMethodCallBody($methodCall, $i);
        }

        if (! empty($methodCalls)) {
            $methodCalls = "\n$methodCalls\n";
        }

        $arguments = implode(', ', $this->compileReferences($definition->getArguments()));

        return
<<<TEMPLATE
\$serviceClassName = {$this->compileReference($definition->getClass())};
if (! is_string(\$serviceClassName)) {
    throw new \RuntimeException(sprintf(
        '%s definition class was not resolved to a string',
        \$definitionName,
    ));
}
if (! class_exists(\$serviceClassName, true)) {
    throw new \RuntimeException(sprintf(
        '%s definition resolved to the class %s, which does no exit',
        \$definitionName,
        \$serviceClassName
    ));
}
\$serviceReflection = new \ReflectionClass(\$serviceClassName);
\$service = \$serviceReflection->newInstanceArgs(array({$arguments})));
$methodCalls
return \$service;
TEMPLATE;
    }

    protected function getFactoryMethodCallBody(MethodDefinition $method, $methodIndex)
    {
        $context = array('service');

        $condition = 'true';
        if (null !== $method->getCondition()) {
            $conditions = implode(
                ' and ',
                $this->referenceResolver->resolveReferences($method->getCondition())
            );
            $condition = $this->compileDslPart($conditions, $context);
        }

        $params = implode(', ', $this->compileReferences($method->getParams(), $context));

        return
<<<TEMPLATE
if ($condition) {
    \$serviceMethod = {$this->compileReference($method->getName(), $context)}
    if (! is_string(\$serviceMethod)) {
        throw new \RuntimeException(sprintf(
            'A method call can only be a string, %s provided, as %d method call for the %s service definition',
            gettype(\$serviceMethod),
            $methodIndex,
            \$definitionName
        ));
    } elseif (! method_exists(\$service, \$serviceMethod)) {
        throw new \RuntimeException(sprintf(
            'Requested method "%s::%s" (index %d) does not exists or is not visible for %s service definition',
            get_class(\$service),
            \$serviceMethod,
            $methodIndex,
            \$definitionName
        ));
    }

    call_user_func_array(
        array(\$service, \$serviceMethod),
        array({$params})
    );
}
TEMPLATE;
    }
}