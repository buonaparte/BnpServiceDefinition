<?php

namespace BnpServiceDefinition\Definition;

use BnpServiceDefinition\Exception;

class ClassDefinition
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var array
     */
    protected $arguments = array();

    /**
     * @var array
     */
    protected $methodCalls = array();

    /**
     * @var boolean
     */
    protected $abstract = false;

    /**
     * @var string
     */
    protected $parent;

    /**
     * @param array $definition
     * @return ClassDefinition
     */
    public static function fromArray(array $definition)
    {
        $class = new self();
        foreach ($definition as $name => $value) {
            // normalize key
            switch (strtolower(str_replace(array('.', '-', '_'), '', $name))) {

                case 'class':
                    $class->setClass($value);
                    break;

                case 'args':
                case 'arguments':
                    $class->setArguments($value);
                    break;

                case 'abstract':
                    $class->setAbstract($value);
                    break;

                case 'parent':
                    $class->setParent($value);
                    break;

                case 'calls':
                case 'methodcalls':
                    $class->setMethodCalls($value);
                    break;
            }
        }

        return $class;
    }

    /**
     * @param string $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param array $methodCalls
     */
    public function setMethodCalls(array $methodCalls)
    {
        $this->methodCalls = array();
        foreach ($methodCalls as $method) {
            $this->addMethodCall($method);
        }
    }

    /**
     * @return array
     */
    public function getMethodCalls()
    {
        return $this->methodCalls;
    }

    /**
     * @param $methodCall
     * @return $this
     * @throws \BnpServiceDefinition\Exception\InvalidArgumentException if invalid method call provided
     */
    public function addMethodCall($methodCall)
    {
        if (! $methodCall instanceof MethodCallDefinition) {
            if (empty($methodCall) || ! is_string($methodCall) && ! is_array($methodCall)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Method name cannot be empty, must be a method name, or a method call array specs, %s provided',
                    gettype($methodCall)
                ));
            }

            $methodCall = is_string($methodCall)
                ? new MethodCallDefinition($methodCall)
                : MethodCallDefinition::fromArray($methodCall);
        }

        foreach ($this->methodCalls as &$method) {
            /** @var $method MethodCallDefinition */
            if ($methodCall->getName() === $method->getName()) {
                $method->setParameters(array_merge($method->getParameters(), $methodCall->getParameters()));

                if (null !== $method->getConditions() || null !== $methodCall->getConditions()) {
                    $method->setConditions(array_merge(
                        null === $method->getConditions() ? array() : $method->getConditions(),
                        null === $methodCall->getConditions() ? array() : $methodCall->getConditions()
                    ));
                }

                return $this;
            }
        }

        $this->methodCalls[] = $methodCall;
        return $this;
    }

    /**
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param array $arguments
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param boolean $abstract
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
    }

    /**
     * @return boolean
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    public function hasParent()
    {
        return null !== $this->parent;
    }
}
