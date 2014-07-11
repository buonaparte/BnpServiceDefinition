<?php

namespace BnpServiceDefinition\Factory;

use BnpServiceDefinition\Definition\DefinitionRepository;
use BnpServiceDefinition\Options\DefinitionOptions;
use BnpServiceDefinition\Service\Evaluator;
use BnpServiceDefinition\Service\Generator;
use BnpServiceDefinition\Exception;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\InitializableInterface;

class DefinitionAbstractFactory implements
    AbstractFactoryInterface,
    ServiceLocatorAwareInterface,
    InitializableInterface
{
    /**
     * @var DefinitionRepository
     */
    protected $definitionRepository;

    /**
     * @var string
     */
    protected $scopeName;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var bool
     */
    protected $generatedFactoryAttached = false;

    /**
     * @var DefinitionOptions
     */
    protected $options;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var Evaluator
     */
    protected $evaluator;

    /**
     * @var Generator
     */
    protected $generator;

    public function __construct(DefinitionRepository $repository = null, $scopeName = null)
    {
        $this->definitionRepository = $repository;
        $this->scopeName = $scopeName;
    }

    protected function getGeneratedFactory()
    {
        $dir = $this->getOptions($this->getServiceLocator())->getDumpDirectory();
        if (empty($dir) || ! is_dir($dir) || ! is_readable($dir) || ! is_writable($dir)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '"%s" is not a valid directory, Dump directory must be both readable and writable',
                $dir
            ));
        }

        $factory = new \stdClass();
        $factory->filename = rtrim($dir, '/')
            . sprintf(
                '/%s_%s.php',
                'BnpGeneratedAbstractFactory',
                null !== $this->scopeName
                ? sprintf('%s_%s', $this->getScopeCanonicalName(), $this->definitionRepository->getChecksum())
                : $this->definitionRepository->getChecksum()
            );
        $factory->class = sprintf(
            'BnpGeneratedAbstractFactory_%s',
            null !== $this->scopeName
            ? sprintf('%s_%s', $this->getScopeCanonicalName(), $this->definitionRepository->getChecksum())
            : $this->definitionRepository->getChecksum()
        );

        return $factory;
    }

    protected function getScopeCanonicalName()
    {
        return preg_replace('@[^\w]@', '', $this->scopeName);
    }

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if ($this->generatedFactoryAttached || null === $this->definitionRepository) {
            return false;
        }

        return $this->definitionRepository->hasDefinition($requestedName);
    }

    protected function getOptions()
    {
        if (null === $this->options) {
            $this->options = $this->getServiceLocator()->get('BnpServiceDefinition\Options\DefinitionOptions');
        }

        return $this->options;
    }

    protected function getConfig()
    {
        if (null === $this->config) {
            $this->config = $this->getServiceLocator()->get('Config');
        }

        return $this->config;
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return mixed
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return $this->getEvaluator()->evaluate($requestedName, $this->definitionRepository);
    }

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->services;
    }

    protected function getDefinitionsConfig($key)
    {
        $config = $this->getConfig($this->getServiceLocator());

        if (! is_array($key)) {
            $key = array($key);
        }

        while (! empty($key) && ! empty($config)) {
            $path = array_shift($key);

            if (isset($config[$path])) {
                $config = $config[$path];
            } else {
                $config = array();
            }
        }

        return isset($config['definitions'])
            ? (array) $config['definitions']
            : array();
    }

    protected function attachGeneratedFactory(ServiceLocatorInterface $serviceLocator)
    {
        if ($this->generatedFactoryAttached || null === $this->definitionRepository
            || ! $this->getOptions()->getDumpFactories()
        ) {
            return;
        }

        $factory = $this->getGeneratedFactory();
        if (! file_exists($factory->filename) || ! is_readable($factory->filename)) {
            $pattern = str_replace(
                $this->definitionRepository->getChecksum(),
                str_repeat('?', strlen($this->definitionRepository->getChecksum())),
                $factory->filename
            );

            foreach (glob($pattern) as $file) {
                if (is_readable($file)) {
                    unlink($file);
                }
            }

            $this->getGenerator()->generate($factory->class, $this->definitionRepository, $factory->filename)
                ->write();
        }

        require_once $factory->filename;

        $factoryClass = $factory->class;
        /** @var $factoryInstance ServiceLocatorAwareInterface */
        $factoryInstance = new $factoryClass($this->scopeName);
        $factoryInstance->setServiceLocator($this->getServiceLocator());

        /** @var $serviceLocator ServiceManager */
        $serviceLocator->addAbstractFactory($factoryInstance);
        $this->generatedFactoryAttached = true;
    }

    public function init()
    {
        if ($this->generatedFactoryAttached || null !== $this->definitionRepository) {
            return;
        }

        $services = $this->getServiceLocator();
        $options = $this->getOptions($services);

        foreach ($options->getDefinitionAwareContainers() as $container => $containerConfig) {
            if (! $services->has($container)) {
                throw new Exception\InvalidArgumentException(sprintf('Inner service locator %s not found', $container));
            }

            /** @var $serviceManager ServiceManager */
            if (! ($serviceManager = $services->get($container)) instanceof ServiceManager) {
                throw new Exception\RuntimeException(sprintf(
                    'Inner service locator %s must be an instance of ServiceManager',
                    $container
                ));
            }

            $factory = new self(new DefinitionRepository($this->getDefinitionsConfig($containerConfig)), $container);
            $factory->setServiceLocator($this->getServiceLocator());
            $factory->attachGeneratedFactory($serviceManager);

            /** @var $serviceManager ServiceManager */
            $serviceManager->addAbstractFactory($factory, false);
        }

        $this->definitionRepository = new DefinitionRepository($this->getDefinitionsConfig('service_manager'));
        $this->attachGeneratedFactory($services);
    }

    /**
     * @return Generator
     */
    protected function getGenerator()
    {
        if (null === $this->generator) {
            $this->generator = $this->getServiceLocator()->get('BnpServiceDefinition\Service\Generator');
        }

        return $this->generator;
    }

    /**
     * @return Evaluator
     */
    protected function getEvaluator()
    {
        if (null === $this->evaluator) {
            $this->evaluator = $this->getServiceLocator()->get('BnpServiceDefinition\Service\Evaluator');
        }

        return $this->evaluator;
    }
}
