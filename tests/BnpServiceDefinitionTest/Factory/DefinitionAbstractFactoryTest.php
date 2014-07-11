<?php

namespace BnpServiceDefinitionTest\Factory;

use BnpServiceDefinition\Definition\DefinitionRepository;
use BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider;
use BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider;
use BnpServiceDefinition\Dsl\Language;
use BnpServiceDefinition\Exception\InvalidArgumentException;
use BnpServiceDefinition\Exception\RuntimeException;
use BnpServiceDefinition\Factory\DefinitionAbstractFactory;
use BnpServiceDefinition\Factory\EvaluatorFactory;
use BnpServiceDefinition\Factory\GeneratorFactory;
use BnpServiceDefinition\Factory\LanguageFactory;
use BnpServiceDefinition\Options\DefinitionOptions;
use BnpServiceDefinition\Service\Generator;
use BnpServiceDefinition\Service\ParameterResolver;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;

class DefinitionAbstractFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    /**
     * @var DefinitionOptions
     */
    protected $options;

    /**
     * @var DefinitionAbstractFactory
     */
    protected $factory;

    /**
     * @var string
     */
    protected $dumpDirectory;

    protected function setUp()
    {
        $this->options = new DefinitionOptions();

        $this->services = new ServiceManager();
        $this->overrideConfig(array(
            'invokables' => array(
                ServiceFunctionProvider::SERVICE_KEY => 'BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider',
                ConfigFunctionProvider::SERVICE_KEY => 'BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider',
                'BnpServiceDefinition\Service\ParameterResolver' => 'BnpServiceDefinition\Service\ParameterResolver',
            ),
            'factories' => array(
                'BnpServiceDefinition\Service\Generator' => new GeneratorFactory(),
                'BnpServiceDefinition\Service\Evaluator' => new EvaluatorFactory(),
                'BnpServiceDefinition\Dsl\Language' => new LanguageFactory()
            ),
            // Mvc Functionality
            'initializers' => array(
                function ($service, ServiceLocatorInterface $sm) {
                    if ($service instanceof ServiceLocatorAwareInterface) {
                        $service->setServiceLocator($sm);
                    }
                    if ($service instanceof ServiceManagerAwareInterface && $sm instanceof ServiceManager) {
                        $service->setServiceManager($sm);
                    }
                }
            )
        ));

        $this->factory = new DefinitionAbstractFactory();
        $this->factory->setServiceLocator($this->services);
        $this->services->addAbstractFactory($this->factory);

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

    protected function overrideConfig(array $config)
    {
        $oldAllowOverride = $this->services->getAllowOverride();

        $this->services->setAllowOverride(true);
        $configInstance = new Config($config);
        $configInstance->configureServiceManager($this->services);

        $this->services->setAllowOverride($oldAllowOverride);
    }

    protected function clearDumpDirectory()
    {
        foreach (glob($this->dumpDirectory . '/*.php') as $file) {
            unlink($file);
        }
    }

    public function dummyDefinitionKeysProvider()
    {
        return array(
            array('foo'),
            array('bar'),
            array('baz')
        );
    }

    /**
     * @param $definition string
     * @dataProvider dummyDefinitionKeysProvider
     */
    public function testCanCreateServiceWithNameDelegatesToDefinitionRepositoryWhenNotCompiled($definition)
    {
        $repository = new DefinitionRepository(array(
            'foo' => array(
                'class' => '\stdClass'
            )
        ));
        $factory = new DefinitionAbstractFactory($repository);
        $this->services->addAbstractFactory($factory);

        $this->assertEquals(
            $repository->hasDefinition($definition),
            $factory->canCreateServiceWithName($this->services, $definition, $definition)
        );
    }

    public function testInitializationIsSkippedWhenHasRepository()
    {
        $this->services->setFactory(
            'BnpServiceDefinition\Options\DefinitionOptions',
            function () {
                throw new \RuntimeException();
            }
        );

        $factory = new DefinitionAbstractFactory(new DefinitionRepository(array()));
        $factory->setServiceLocator($this->services);
        $this->services->addAbstractFactory($factory);

        try {
            $factory->init();
        } catch (\Exception $e) {
            $this->fail('Exception must not be thrown here');
        }
    }

    public function testRootLocatorPullsConfigFromServiceManagerConfig()
    {
        $this->services->setService('BnpServiceDefinition\Options\DefinitionOptions', $this->options);

        $this->overrideConfig(array(
            'services' => array(
                'Config' => array(
                    'service_manager' => array(
                        'definitions' => array(
                            'foo' => array(
                                'class' => '\stdClass'
                            )
                        )
                    )
                )
            )
        ));

        $this->factory->init();
        $this->assertInstanceOf('\stdClass', $this->services->get('foo'));
    }

    public function testCanAttachScopedLocatorWithDefinitions()
    {
        $this->options->setDefinitionAwareContainers(array(
            'container' => 'container_config'
        ));
        $this->services->setService('BnpServiceDefinition\Options\DefinitionOptions', $this->options);

        $this->overrideConfig(array(
            'services' => array(
                'Config' => array(
                    'container_config' => array(
                        'services' => array(
                            'foo' => 'bar'
                        ),
                        'definitions' => array(
                            'baz' => array(
                                'class' => '\stdClass'
                            )
                        )
                    )
                )
            ),
            'factories' => array(
                'container' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('Config');
                    return new ServiceManager(new Config($config['container_config']));
                }
            )
        ));

        $this->factory->init();
        /** @var $container ServiceLocatorInterface */
        $container = $this->services->get('container');

        $this->assertEquals('bar', $container->get('foo'));
        $this->assertInstanceOf('\stdClass', $container->get('baz'));
    }

    public function testCanAttachScopedLocatorWithDefinitionsFromANestedConfig()
    {
        $this->options->setDefinitionAwareContainers(array(
            'container' => array('config_path', 'container_config')
        ));
        $this->services->setService('BnpServiceDefinition\Options\DefinitionOptions', $this->options);

        $this->overrideConfig(array(
            'services' => array(
                'Config' => array(
                    'config_path' => array(
                        'container_config' => array(
                            'services' => array(
                                'foo' => 'bar'
                            ),
                            'definitions' => array(
                                'baz' => array(
                                    'class' => '\stdClass'
                                )
                            )
                        )
                    )
                )
            ),
            'factories' => array(
                'container' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('Config');
                    return new ServiceManager(new Config($config['config_path']['container_config']));
                }
            )
        ));

        $this->factory->init();
        /** @var $container ServiceLocatorInterface */
        $container = $this->services->get('container');

        $this->assertEquals('bar', $container->get('foo'));
        $this->assertInstanceOf('\stdClass', $container->get('baz'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWillThrowExceptionWhenScopedLocatorNotFound()
    {
        $this->options->setDefinitionAwareContainers(array(
            'container' => 'container_config'
        ));
        $this->services->setService('BnpServiceDefinition\Options\DefinitionOptions', $this->options);

        $this->factory->init();
    }

    /**
     * @expectedException RuntimeException
     */
    public function testWillThrowExceptionWhenScopedLocatorIsNotAServiceManager()
    {
        $this->options->setDefinitionAwareContainers(array(
            'container' => 'container_config'
        ));
        $this->services->setService('BnpServiceDefinition\Options\DefinitionOptions', $this->options);

        $locator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $this->services->setService('container', $locator);

        $this->factory->init();
    }

    public function testSilentPassesAttachingScopedLocatorWithoutConfig()
    {
        $this->options->setDefinitionAwareContainers(array(
            'container' => 'unknown_config'
        ));
        $this->services->setService('BnpServiceDefinition\Options\DefinitionOptions', $this->options);

        $this->overrideConfig(array(
            'services' => array(
                'Config' => array(
                    'container_config' => array(
                        'services' => array(
                            'foo' => 'bar'
                        ),
                        'definitions' => array(
                            'baz' => array(
                                'class' => '\stdClass'
                            )
                        )
                    )
                )
            ),
            'factories' => array(
                'container' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('Config');
                    return new ServiceManager(new Config($config['container_config']));
                }
            )
        ));

        $this->factory->init();
        /** @var $container ServiceLocatorInterface */
        $container = $this->services->get('container');

        $this->assertEquals('bar', $container->get('foo'));
        $this->assertFalse($container->has('baz'));
    }

    public function testInjectsRootLocatorIntoAttachedScopedLocator()
    {
        $this->options->setDefinitionAwareContainers(array(
            'container' => 'container_config'
        ));
        $this->services->setService('BnpServiceDefinition\Options\DefinitionOptions', $this->options);

        $this->overrideConfig(array(
            'services' => array(
                'Config' => array(
                    'container_config' => array(
                        'services' => array(
                            'foo' => '\stdClass'
                        ),
                        'definitions' => array(
                            'baz' => array(
                                'class' => array('type' => 'service', 'value' => 'foo')
                            )
                        )
                    )
                ),
                'foo' => '\ArrayObject'
            ),
            'factories' => array(
                'container' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('Config');
                    return new ServiceManager(new Config($config['container_config']));
                }
            )
        ));

        $this->factory->init();
        /** @var $container ServiceLocatorInterface */
        $container = $this->services->get('container');

        $this->assertInstanceOf('\ArrayObject', $container->get('baz'));
    }

    public function testCanGenerateAndDelegateToACompiledVersion()
    {
        $this->options->setDumpDirectory($this->dumpDirectory);
        $this->options->setDumpFactories(true);
        $this->services->setService('BnpServiceDefinition\Options\DefinitionOptions', $this->options);

        $this->overrideConfig(array(
            'services' => array(
                'Config' => array(
                    'foo.class' => '\ArrayObject',
                    'service_manager' => array(
                        'definitions' => $definitions = array(
                            'foo' => array(
                                'class' => array('type' => 'config', 'value' => 'foo.class'),
                                'arguments' => array(
                                    array('type' => 'value', 'value' => array(1, 2, 3))
                                ),
                                'calls' => array(
                                    array(
                                        'exchangeArray',
                                        array(array('type' => 'value', 'value' => array('elt'))),
                                        array(array('type' => 'dsl', 'value' => '3 == service.count()'))
                                    )
                                )
                            )
                        )
                    )
                )
            ),
            'factories' => array(
                // Ensure Evaluator is not used
                'BnpServiceDefinition\Service\Evaluator' => function () {
                    throw new \RuntimeException();
                }
            )
        ));

        $repo = new DefinitionRepository($definitions);

        $this->factory->init();
        $this->assertFileExists($this->dumpDirectory . '/BnpGeneratedAbstractFactory_' . $repo->getChecksum() . '.php');
        $this->assertTrue(class_exists('BnpGeneratedAbstractFactory_' . $repo->getChecksum()));

        /** @var $foo \ArrayObject */
        $foo = $this->services->get('foo');
        $this->assertInstanceOf('\ArrayObject', $foo);
        $this->assertEquals(array('elt'), $foo->getArrayCopy());
    }

    public function testWillNotRelyOnGeneratorOrEvaluatorWhenACompiledVersionAlreadyExists()
    {
        $this->options->setDumpDirectory($this->dumpDirectory);
        $this->options->setDumpFactories(true);
        $this->services->setService('BnpServiceDefinition\Options\DefinitionOptions', $this->options);

        $this->overrideConfig(array(
            'services' => array(
                'Config' => array(
                    'foo.class' => '\ArrayObject',
                    'service_manager' => array(
                        'definitions' => $definitions = array(
                                'foo' => array(
                                    'class' => array('type' => 'config', 'value' => 'foo.class'),
                                    'arguments' => array(
                                        array('type' => 'value', 'value' => array(1, 2, 3))
                                    ),
                                    'calls' => array(
                                        array(
                                            'exchangeArray',
                                            array(array('type' => 'value', 'value' => array('elt'))),
                                            array(array('type' => 'dsl', 'value' => '3 == service.count()'))
                                        )
                                    )
                                )
                            )
                    )
                )
            ),
            'factories' => array(
                // Ensure Evaluator is not used
                'BnpServiceDefinition\Service\Evaluator' => function () {
                    throw new \RuntimeException();
                },
                // Ensure Generator is not used
                'BnpServiceDefinition\Service\Generator' => function () {
                    throw new \RuntimeException();
                }
            )
        ));

        $repo = new DefinitionRepository($definitions);

        /** @var $language Language */
        $language = $this->services->get('BnpServiceDefinition\Dsl\Language');
        /** @var $resolver ParameterResolver */
        $resolver = $this->services->get('BnpServiceDefinition\Service\ParameterResolver');

        $generator = new Generator($language, $resolver, $this->options);
        $file = $generator->generate(
            'BnpGeneratedAbstractFactory_' . $repo->getChecksum(),
            $repo,
            $filename = $this->dumpDirectory . '/BnpGeneratedAbstractFactory_' . $repo->getChecksum() . '.php'
        );
        $file->write();

        $this->assertFileExists($filename);
        $this->factory->init();

        /** @var $foo \ArrayObject */
        $foo = $this->services->get('foo');
        $this->assertInstanceOf('\ArrayObject', $foo);
        $this->assertEquals(array('elt'), $foo->getArrayCopy());
    }

    public function testWillRemoveOlderAlikeDumpsBeforeRegenerate()
    {
        $this->options->setDumpDirectory($this->dumpDirectory);
        $this->options->setDumpFactories(true);
        $this->services->setService('BnpServiceDefinition\Options\DefinitionOptions', $this->options);

        $this->overrideConfig(array(
            'services' => array(
                'Config' => array(
                    'foo.class' => '\ArrayObject',
                    'service_manager' => array(
                        'definitions' => $definitions = array(
                                'foo' => array(
                                    'class' => array('type' => 'config', 'value' => 'foo.class'),
                                    'arguments' => array(
                                        array('type' => 'value', 'value' => array(1, 2, 3))
                                    ),
                                    'calls' => array(
                                        array(
                                            'exchangeArray',
                                            array(array('type' => 'value', 'value' => array('elt'))),
                                            array(array('type' => 'dsl', 'value' => '3 == service.count()'))
                                        )
                                    )
                                )
                            )
                    )
                )
            )
        ));

        unset($definitions['foo']['calls']);
        $repo = new DefinitionRepository($definitions);

        /** @var $language Language */
        $language = $this->services->get('BnpServiceDefinition\Dsl\Language');
        /** @var $resolver ParameterResolver */
        $resolver = $this->services->get('BnpServiceDefinition\Service\ParameterResolver');

        $generator = new Generator($language, $resolver, $this->options);
        $file = $generator->generate(
            'BnpGeneratedAbstractFactory_' . $repo->getChecksum(),
            $repo,
            $filename = $this->dumpDirectory . '/BnpGeneratedAbstractFactory_' . $repo->getChecksum() . '.php'
        );
        $file->write();

        $this->assertFileExists($filename);

        $this->factory->init();
        $this->assertFileNotExists($filename);

        /** @var $foo \ArrayObject */
        $foo = $this->services->get('foo');
        $this->assertInstanceOf('\ArrayObject', $foo);
        $this->assertEquals(array('elt'), $foo->getArrayCopy());
    }

    public function testWillRemoveOlderAlikeScopeLocatorDumpsBeforeRegenerate()
    {
        $this->options->setDefinitionAwareContainers(array(
            'container' => 'container_config'
        ));
        $this->options->setDumpDirectory($this->dumpDirectory);
        $this->options->setDumpFactories(true);
        $this->services->setService('BnpServiceDefinition\Options\DefinitionOptions', $this->options);

        $this->overrideConfig(array(
            'services' => array(
                'Config' => array(
                    'foo.class' => '\ArrayObject',
                    'container_config' => array(
                        'definitions' => $definitions = array(
                            'foo' => array(
                                'class' => array('type' => 'config', 'value' => 'foo.class'),
                                'arguments' => array(
                                    array('type' => 'value', 'value' => array(1, 2, 3))
                                ),
                                'calls' => array(
                                    array(
                                        'exchangeArray',
                                        array(array('type' => 'value', 'value' => array('elt'))),
                                        array(array('type' => 'dsl', 'value' => '3 == service.count()'))
                                    )
                                )
                            )
                        )
                    )
                ),
            ),
            'factories' => array(
                'container' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('Config');
                    return new ServiceManager(new Config($config));
                }
            )
        ));

        unset($definitions['foo']['calls']);
        $repo = new DefinitionRepository($definitions);

        /** @var $language Language */
        $language = $this->services->get('BnpServiceDefinition\Dsl\Language');
        /** @var $resolver ParameterResolver */
        $resolver = $this->services->get('BnpServiceDefinition\Service\ParameterResolver');

        $generator = new Generator($language, $resolver, $this->options);
        $file = $generator->generate(
            'BnpGeneratedAbstractFactory_container_' . $repo->getChecksum(),
            $repo,
            $filename = $this->dumpDirectory . '/BnpGeneratedAbstractFactory_container_' . $repo->getChecksum() . '.php'
        );
        $file->write();

        $this->assertFileExists($filename);

        $this->factory->init();
        $this->assertFileNotExists($filename);

        /** @var $foo \ArrayObject */
        $foo = $this->services->get('container')->get('foo');
        $this->assertInstanceOf('\ArrayObject', $foo);
        $this->assertEquals(array('elt'), $foo->getArrayCopy());
    }

    public function testUsesCanonicalNameForScopedLocatorWhenCompiling()
    {
        $this->options->setDefinitionAwareContainers(array(
            'a container' => 'container_config'
        ));
        $this->options->setDumpFactories(true);
        $this->options->setDumpDirectory($this->dumpDirectory);
        $this->services->setService('BnpServiceDefinition\Options\DefinitionOptions', $this->options);

        $this->overrideConfig(array(
            'services' => array(
                'Config' => array(
                    'container_config' => array(
                        'services' => array(
                            'foo' => '\stdClass'
                        ),
                        'definitions' => $scopedDefinitions = array(
                            'baz' => array(
                                'class' => array('type' => 'service', 'value' => 'foo')
                            )
                        )
                    ),
                    'service_manager' => array(
                        'definitions' => $definitions = array(
                            'foo' => array(
                                'class' => '\ArrayObject',
                            )
                        )
                    )
                ),
                'foo' => '\ArrayObject'
            ),
            'factories' => array(
                'a container' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('Config');
                    return new ServiceManager(new Config($config['container_config']));
                }
            )
        ));

        $scopedDefinitionsRepo = new DefinitionRepository($scopedDefinitions);
        $definitionsRepo = new DefinitionRepository($definitions);

        $this->factory->init();

        $this->assertFileExists(
            $this->dumpDirectory . '/BnpGeneratedAbstractFactory_' . $definitionsRepo->getChecksum() . '.php'
        );
        $this->assertFileExists(
            $this->dumpDirectory.'/BnpGeneratedAbstractFactory_acontainer_'.$scopedDefinitionsRepo->getChecksum().'.php'
        );
    }

    public function invalidDumpDirectoryProvider()
    {
        return array(
            array('directory.does.not.exist'),
            array('/'),
            array('')
        );
    }

    /**
     * @param $dir
     * @dataProvider invalidDumpDirectoryProvider
     * @expectedException InvalidArgumentException
     */
    public function testWillThrowExceptionWhenInvalidOrWithoutPermissionsDumpDirectoryProvided($dir)
    {
        $this->options->setDumpDirectory($dir);
        $this->options->setDumpFactories(true);
        $this->services->setService('BnpServiceDefinition\Options\DefinitionOptions', $this->options);

        $this->overrideConfig(array(
            'services' => array(
                'Config' => array(
                    'service_manager' => array(
                        'definitions' => array(
                            'foo' => array(
                                'class' => '\stdClass'
                            )
                        )
                    )
                )
            )
        ));

        $this->factory->init();
    }
}
