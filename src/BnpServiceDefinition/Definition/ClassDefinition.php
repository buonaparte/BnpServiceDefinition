<?php

namespace BnpServiceDefinition\Definition;

use Zend\Stdlib\Hydrator\ClassMethods;

class ClassDefinition
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * @var array
     */
    protected $methodCalls;

    /**
     * @var boolean
     */
    protected $abstract;

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
        $hydrator = new ClassMethods();
        return $hydrator->hydrate($definition, new static());
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
            if (is_array($method)) {
                $this->addMethodCall(array_shift($method), array_shift($method));
            } else {
                $this->addMethodCall($method);
            }
        }
    }

    /**
     * @return array
     */
    public function getMethodCalls()
    {
        return $this->methodCalls;
    }

    public function addMethodCall($method, $arguments = array())
    {
        if ($method instanceof MethodDefinition) {
            $this->methodCalls[] = $method;
            return $this;
        }

        if (empty($method)) {
            throw new \RuntimeException('Method name cannot be empty');
        }

        $this->methodCalls[] = new MethodDefinition($method, $arguments);
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
