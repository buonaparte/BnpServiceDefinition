<?php

namespace BnpServiceDefinitionTest\Service;

use BnpServiceDefinition\Definition\DefinitionRepository;
use BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider;
use BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider;
use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Options\DefinitionOptions;
use BnpServiceDefinition\Service\Generator;
use BnpServiceDefinition\Service\ParameterResolver;
use Zend\Code\Generator\MethodGenerator;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceManager;

class GeneratorTest extends DefinitionFactoryAbstractTest
{
    /**
     * @var DefinitionOptions
     */
    protected $options;

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
     * @var Generator
     */
    protected $generator;

    /**
     * @var string
     */
    protected $dumpDirectory;

    /**
     * @var int
     */
    protected $immutableGeneratedFactoryMethodsCount;

    protected function setUp()
    {
        $this->options = new DefinitionOptions();
        $this->parameterResolver = new ParameterResolver();
        $this->services = new ServiceManager();

        $this->language = new Language();
        $extensions = array(
            ConfigFunctionProvider::SERVICE_KEY => new ConfigFunctionProvider(),
            ServiceFunctionProvider::SERVICE_KEY => new ServiceFunctionProvider()
        );
        foreach ($extensions as $name => $extension) {
            if ($extension instanceof ServiceLocatorAwareInterface) {
                $extension->setServiceLocator($this->services);
            }

            $this->language->registerExtension($extension);
            $this->services->setService($name, $extension);
        }

        $this->generator = new Generator($this->language, $this->parameterResolver, $this->options);

        $boot = $this->generator->generate('SampleClassName', new DefinitionRepository(array()));
        $this->immutableGeneratedFactoryMethodsCount = count($boot->getClass()->getMethods());

        $path = __DIR__ . '/__dump_dir';
        if (! is_dir($path) || ! is_readable($path) || ! is_writable($path)) {
            $this->fail('Must have a valid, both readable and writable directory');
        }

        $this->dumpDirectory = $path;
    }

    protected function tearDown()
    {
        $this->clearDumpDirectory();
    }

    protected function clearDumpDirectory()
    {
        foreach (glob($this->dumpDirectory . '/*.php') as $file) {
            unlink($file);
        }
    }

    public function testCanGenerateEmptyDefinitions()
    {
        $out = $this->generator->generate('SampleClassName', new DefinitionRepository(array()));

        $this->assertInstanceOf('Zend\Code\Generator\FileGenerator', $out);
        $this->assertCount(1, $out->getClasses());
        $this->assertEquals('SampleClassName', $out->getClass()->getName());
        $this->assertNull($out->getFilename());
    }

    public function testAddsFilenameIfSpecified()
    {
        $out = $this->generator->generate('SampleClassName', new DefinitionRepository(array()), 'a_file.php');

        $this->assertInstanceOf('Zend\Code\Generator\FileGenerator', $out);
        $this->assertEquals('a_file.php', $out->getFilename());
    }

    public function testCanGenerateForDefinitionsWithSameCanonicalNameWithoutCollision()
    {
        $out = $this->generator->generate(
            'SampleClassName',
            new DefinitionRepository($definitions = array(
                'A\Service' => array(
                    'class' => '\stdClass'
                ),
                'A\\Service' => array(
                    'class' => '\ArrayObject'
                ),
                'ServiceLocator' => array(
                    'class' => '\stdClass'
                )
            ))
        );
        $nameCanonical = 'AService';

        $getMethodName = function (MethodGenerator $method) {
            return $method->getName();
        };

        $this->assertInstanceOf('Zend\Code\Generator\FileGenerator', $out);
        $this->assertCount(
            $this->immutableGeneratedFactoryMethodsCount + count(array_keys($definitions)),
            $out->getClass()->getMethods()
        );

        $this->assertContains("get$nameCanonical", array_map($getMethodName, $out->getClass()->getMethods()));
        $this->assertContains('getServiceLocator1', array_map($getMethodName, $out->getClass()->getMethods()));
        for ($i=1; $i<count(array_keys($definitions)) - 1; $i++) {
            $this->assertContains("get$nameCanonical$i", array_map($getMethodName, $out->getClass()->getMethods()));
        }
    }

    protected function getComplexValidDefinitionForName($name, $className)
    {
        return $this->generator->generate(
            $className,
            new DefinitionRepository(array(
                $name => array(
                    'class' => '\ArrayObject',
                    'arguments' => array(
                        array('type' => 'value', 'value' => array())
                    ),
                    'calls' => array(
                        array(
                            'name' => 'exchangeArray',
                            'parameters' => array(
                                array('type' => 'value', 'value' => array('firstItem'))
                            )
                        ),
                        array(
                            'name' => 'exchangeArray',
                            'parameters' => array(
                                array('type' => 'value', 'value' => array('firstItem', 'secondItem'))
                            ),
                            'conditions' => array(
                                array('type' => 'dsl', 'value' => '0 == service.count()')
                            )
                        )
                    )
                )
            ))
        );
    }

    public function testGeneratesComplexDefinitions()
    {
        $out = $this->getComplexValidDefinitionForName('a_service', 'SomeClass');

        $this->assertInstanceOf('Zend\Code\Generator\FileGenerator', $out);
        $this->assertCount($this->immutableGeneratedFactoryMethodsCount + 1, $out->getClass()->getMethods());
    }

    protected function getUniqueDumpedFactoryClassName()
    {
        return 'DumpedAbstractFactoryClassName' . hash('sha1', microtime());
    }

    public function testGeneratedCodeDoesNotContainSyntaxErrors()
    {
        $class = $this->getUniqueDumpedFactoryClassName();
        $this->getComplexValidDefinitionForName('a_service', $class)
            ->setFilename($filename = "{$this->dumpDirectory}/$class.php")
            ->write();

        if (function_exists('exec')) {
            $self = $this;
            set_error_handler(
                function ($level, $error) use ($self) {
                    $self->fail(sprintf('An error occurred (# %s) with message "%s"', $level, $error));
                }
            );

            $out = exec(sprintf('%s -l %s', PHP_BIN_PATH, $filename));
            if (strstr(strtolower($out), 'parse error')) {
                $this->fail(sprintf('Generated file contains syntax errors: "%s"', $out));
            }

            restore_error_handler();
        } else {
            $this->markTestSkipped('Exec is not allowed on your server');
        }
    }

    public function testGeneratedCodeContainsAnAbstractFactory()
    {
        $out = $this->generator->generate(
            $class = $this->getUniqueDumpedFactoryClassName(),
            new DefinitionRepository(array())
        );

        $out->setFilename($filename = "{$this->dumpDirectory}/$class.php")
            ->write();

        require $filename;

        $this->assertTrue(class_exists($class));

        $factory = new $class();
        $this->assertInstanceOf('Zend\ServiceManager\AbstractFactoryInterface', $factory);
    }

    public function testGeneratorSkipsNonTerminalDefinitions()
    {
        $out = $this->generator->generate(
            'SomeClass',
            new DefinitionRepository($definitions = array(
                'first' => array(
                    'class' => '\stdClass'
                ),
                'second' => array(
                    'class' => '\ArrayObject'
                ),
                'third' => array(
                    'abstract' => true,
                    'class' => '\stdClass'
                )
            ))
        );

        $this->assertInstanceOf('Zend\Code\Generator\FileGenerator', $out);
        $this->assertCount($this->immutableGeneratedFactoryMethodsCount + 2, $out->getClass()->getMethods());
    }

    public function testGeneratedFactoryMethodsBodies()
    {
        $out = $this->generator->generate(
            'SomeClass',
            new DefinitionRepository($definitions = array(
                'first' => array(
                    'class' => '\ArrayObject',
                    'calls' => array(
                        array('append', array('value'))
                    )
                ),
                'second' => array(
                    'class' => '\ArrayObject',
                    'calls' => array(
                        array('append', array('value'), array(true))
                    )
                )
            ))
        );

        $first = $out->getClass()->getMethod('getFirst');
        $second = $out->getClass()->getMethod('getSecond');

        $this->assertInstanceOf('Zend\Code\Generator\MethodGenerator', $first);
        $this->assertInstanceOf('Zend\Code\Generator\MethodGenerator', $second);

        $firstBody =
            <<<TEMPLATE
set_error_handler(
    function (\$level, \$message) use (\$definitionName) {
        throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
            'A %d level error occurred (message: "%s") while creating %s service from compiled Abstract Factory',
            \$level,
            \$message,
            \$definitionName
        ));
    }
);

\$serviceClassName = "\\\ArrayObject";
if (! is_string(\$serviceClassName)) {
    throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
        '%s definition class was not resolved to a string',
        \$definitionName
    ));
}
if (! class_exists(\$serviceClassName, true)) {
    throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
        '%s definition resolved to the class %s, which does no exit',
        \$definitionName,
        \$serviceClassName
    ));
}
\$serviceReflection = new \ReflectionClass(\$serviceClassName);
\$service = \$serviceReflection->newInstanceArgs(array());


\$serviceMethod = "append";
if (! is_string(\$serviceMethod)) {
    throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
        'A method call can only be a string, %s provided, as %d method call for the %s service definition',
        gettype(\$serviceMethod),
        0,
        \$definitionName
    ));
} elseif (! method_exists(\$service, \$serviceMethod)) {
    throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
        'Requested method "%s::%s" (index %d) does not exists or is not visible for %s service definition',
        get_class(\$service),
        \$serviceMethod,
        0,
        \$definitionName
    ));
}

call_user_func_array(
    array(\$service, \$serviceMethod),
    array("value")
);

restore_error_handler();

return \$service;
TEMPLATE;

        $secondBody =
<<<TEMPLATE
set_error_handler(
    function (\$level, \$message) use (\$definitionName) {
        throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
            'A %d level error occurred (message: "%s") while creating %s service from compiled Abstract Factory',
            \$level,
            \$message,
            \$definitionName
        ));
    }
);

\$serviceClassName = "\\\ArrayObject";
if (! is_string(\$serviceClassName)) {
    throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
        '%s definition class was not resolved to a string',
        \$definitionName
    ));
}
if (! class_exists(\$serviceClassName, true)) {
    throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
        '%s definition resolved to the class %s, which does no exit',
        \$definitionName,
        \$serviceClassName
    ));
}
\$serviceReflection = new \ReflectionClass(\$serviceClassName);
\$service = \$serviceReflection->newInstanceArgs(array());


if (true) {
    \$serviceMethod = "append";
    if (! is_string(\$serviceMethod)) {
        throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
            'A method call can only be a string, %s provided, as %d method call for the %s service definition',
            gettype(\$serviceMethod),
            0,
            \$definitionName
        ));
    } elseif (! method_exists(\$service, \$serviceMethod)) {
        throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
            'Requested method "%s::%s" (index %d) does not exists or is not visible for %s service definition',
            get_class(\$service),
            \$serviceMethod,
            0,
            \$definitionName
        ));
    }
    
    call_user_func_array(
        array(\$service, \$serviceMethod),
        array("value")
    );
}

restore_error_handler();

return \$service;
TEMPLATE;

        $this->assertEquals($firstBody, $first->getBody());
        $this->assertEquals($secondBody, $second->getBody());
    }

    protected function createDefinitionWithName($name, DefinitionRepository $repository)
    {
        $out = $this->generator->generate(
            $class = "Evaluate_{$this->getUniqueDumpedFactoryClassName()}",
            $repository
        );

        $out->setFilename($filename = "{$this->dumpDirectory}/$class.php")
            ->write();

        require $filename;

        $factory = new $class();
        $this->services->addAbstractFactory($factory);
        /** @var $factory ServiceLocatorAwareInterface */
        $factory->setServiceLocator($this->services);

        return $this->services->get($name);
    }

    protected function getServiceManager()
    {
        return $this->services;
    }
}
