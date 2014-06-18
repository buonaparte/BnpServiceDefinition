<?php

namespace BnpServiceDefinition\Factory;

use BnpServiceDefinition\Definition\DefinitionRepository;
use BnpServiceDefinition\Options\DefinitionOptions;
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
     * @var string
     */
    protected $dumpedFactoryFilename;

    public function __construct(DefinitionRepository $repository = null, $scopeName = null)
    {
        $this->definitionRepository = $repository;
        $this->scopeName = $scopeName;
    }

    /**
     * @var DefinitionOptions
     */
    protected $options;

    /**
     * @var array
     */
    protected $config;

    protected function getDumpedFactoryName()
    {
        if (null === $this->definitionRepository) {
            return null;
        }

        $dir = $this->getOptions($this->getServiceLocator())->getDumpDirectory();
        if (! is_dir($dir) || ! is_readable($dir) || ! is_writable($dir)) {
            throw new \RuntimeException();
        }

        return rtrim($dir, '/')
            . sprintf('/%s.php',
                null !== $this->scopeName
                    ? sprintf('%s_%s', $this->scopeName, $this->definitionRepository->getChecksum())
                    : $this->definitionRepository->getChecksum()
            );
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
        $filename = $this->getDumpedAbstractFactoryName();
        if (file_exists($filename) && is_readable($filename)) {
            /** @var $services ServiceManager */
            $services = $this->getServiceLocator();
            $services->addAbstractFactory()
        }
        return $this->definitionRepository->hasDefinition($requestedName);
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
        $parser = new Parser();
    }

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
        $this->init();
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

        return isset($config[$key]['definitions'])
            ? (array) $config[$key]['definitions']
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

            /** @var $serviceManager ServiceManager */
            $serviceManager->addAbstractFactory(
                new self(new DefinitionRepository($this->getDefinitionsConfig($containerConfig))), false);
        }

        $this->definitionRepository = new DefinitionRepository($this->getDefinitionsConfig('service_manager'));
    }
}