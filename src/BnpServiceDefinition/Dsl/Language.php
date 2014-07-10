<?php

namespace BnpServiceDefinition\Dsl;

use BnpServiceDefinition\Dsl\Extension\Feature\ContextVariablesProviderInterface;
use BnpServiceDefinition\Dsl\Extension\Feature\FunctionProviderInterface;
use BnpServiceDefinition\Options\DefinitionOptions;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\InitializableInterface;

class Language extends ExpressionLanguage implements
    ServiceLocatorAwareInterface,
    InitializableInterface
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * @var array
     */
    protected $context = array();

    /**
     * @var array
     */
    protected $extensions = array();

    /**
     * @var DefinitionOptions
     */
    protected $options;

    /**
     * @var array
     */
    protected $config;

    public function __construct(ParserCacheInterface $cache = null)
    {
        parent::__construct($cache);
    }

    public function registerExtension($extension)
    {
        $this->extensions[] = $extension;
        return $this;
    }

    public function evaluate($expression, $values = array())
    {
        $this->init();
        return parent::evaluate($expression, array_merge($this->context, $values));
    }

    public function compile($expression, $names = array())
    {
        $this->init();
        return parent::compile($expression, $names);
    }

    /**
     * Init an object
     *
     * @return void
     */
    public function init()
    {
        if ($this->initialized) {
            return;
        }

        foreach ($this->extensions as $extension) {
            if (is_string($extension)) {
                if (null === $this->getServiceLocator() || ! $this->services->has($extension)) {
                    continue;
                }

                try {
                    $extension = $this->services->get($extension);
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (! is_object($extension)) {
                continue;
            }

            if ($extension instanceof ContextVariablesProviderInterface) {
                $context = $extension->getContextVariables();
                if ($context instanceof \Traversable) {
                    $context = ArrayUtils::iteratorToArray($context, false);
                }

                if (! ArrayUtils::hasNumericKeys($context)) {
                    $this->context = array_merge($this->context, $context);
                }
            }

            if ($extension instanceof FunctionProviderInterface) {
                $evaluator = $extension->getEvaluator($this->context);
                $compiler = $extension->getCompiler();

                if (! is_callable($evaluator) || ! is_callable($compiler)) {
                    continue;
                }

                $this->register($extension->getName(), $compiler, $evaluator);
            }
        }

        $this->initialized = true;
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
}
