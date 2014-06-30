<?php

namespace BnpServiceDefinition\Factory;

use BnpServiceDefinition\Definition\DefinitionRepository;
use BnpServiceDefinition\Options\DefinitionOptions;
use BnpServiceDefinition\Service\Evaluator;
use BnpServiceDefinition\Service\Generator;
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
        if (null === $this->definitionRepository) {
            return null;
        }

        $dir = $this->getOptions($this->getServiceLocator())->getDumpDirectory();
        if (! is_dir($dir) || ! is_readable($dir) || ! is_writable($dir)) {
            throw new \RuntimeException();
        }

        $factory = new \stdClass();
        $factory->filename = rtrim($dir, '/')
            . sprintf('/%s.php',
                null !== $this->scopeName
                    ? sprintf('%s_%s', $this->scopeName, $this->definitionRepository->getChecksum())
                    : $this->definitionRepository->getChecksum()
            );
        $factory->class = sprintf('BnpGeneratedAbstractFactory_%s', $this->definitionRepository->getChecksum());
        if (null !== $this->options->getDumpedAbstractFactoriesNamespace()) {
            $factory->class = rtrim($this->options->getDumpedAbstractFactoriesNamespace(), '\\') . $factory->class;
        }

        return $factory;
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
        if ($this->generatedFactoryAttached) {
            return false;
        }

        if (! $this->getOptions($serviceLocator)->getDumpFactories()) {
            return $this->definitionRepository->hasDefinition($requestedName);
        }

        $factory = $this->getGeneratedFactory();
        if (! file_exists($factory->filename) || ! is_readable($factory->filename)) {
            $this->getGenerator()->generate($this->definitionRepository, $factory->filename)->generate();
        }

        require_once $factory->filename;
        /** @var $serviceLocator ServiceManager */
        $serviceLocator->addAbstractFactory($factory->class);
        $this->generatedFactoryAttached = true;

        return false;
    }

    protected function getOptions(ServiceLocatorInterface $serviceLocator)
    {
        if (null === $this->options) {
            $this->options = $serviceLocator->get('BnpServiceDefinition\Options\DefinitionOptions');
        }

        return $this->options;
    }

    protected function getConfig(ServiceLocatorInterface $serviceLocator)
    {
        if (null === $this->config) {
            $this->config = $serviceLocator->get('Config');
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

    public function init()
    {
        if (null !== $this->definitionRepository) {
            return;
        }

        $services = $this->getServiceLocator();
        $options = $this->getOptions($services);

        foreach ($options->getDefinitionAwareContainers() as $container => $containerConfig) {
            if (! $services->has($container)) {
                throw new \InvalidArgumentException(sprintf('Inner service locator %s not found', $container));
            }

            if (! ($serviceManager = $services->get($container)) instanceof ServiceManager) {
                throw new \InvalidArgumentException(sprintf(
                    'Inner service locator %s must be an instance of ServiceManager', $container));
            }

            $factory = new self(new DefinitionRepository($this->getDefinitionsConfig($containerConfig)));
            $factory->setServiceLocator($this->getServiceLocator());

            /** @var $serviceManager ServiceManager */
            $serviceManager->addAbstractFactory($factory, false);
        }

        $this->definitionRepository = new DefinitionRepository($this->getDefinitionsConfig('service_manager'));
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