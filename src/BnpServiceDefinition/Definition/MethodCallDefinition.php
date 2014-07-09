<?php

namespace BnpServiceDefinition\Definition;

use BnpServiceDefinition\Exception;
use Zend\Stdlib\ArrayUtils;

class MethodCallDefinition
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var string|array
     */
    protected $conditions;

    public function __construct($name, array $params = array(), $condition = null)
    {
        $this->setName($name);
        $this->setParameters($params);
        $this->setConditions($condition);
    }

    /**
     * @param array $specs
     * @return MethodCallDefinition
     * @throws Exception\InvalidArgumentException
     */
    public static function fromArray(array $specs)
    {
        if (ArrayUtils::isList($specs)) {
            $name = array_shift($specs);
            $parameters = array_shift($specs);
            $conditions = array_shift($specs);

            $specs = array(
                'name' => $name,
                'parameters' => null !== $parameters
                        ? (! is_array($parameters) ? array($parameters) : $parameters)
                        : array(),
                'conditions' => $conditions
            );
        }

        if (! isset($specs['name'])) {
            throw new Exception\InvalidArgumentException(
                'MethodDefinition expects at least a method name, under "name" key or first list argument'
            );
        }

        $methodCall = new self($specs['name']);
        unset($specs['name']);
        foreach ($specs as $name => $value) {
            // normalize key
            switch (strtolower(str_replace(array('.', '-', '_'), '', $name))) {

                case 'params':
                case 'parameters':
                    $methodCall->setParameters($value);
                    break;

                case 'condition':
                case 'conditions':
                    $methodCall->setConditions($value);
                    break;
            }
        }

        return $methodCall;
    }

    /**
     * @param array|string $condition
     */
    public function setConditions($condition)
    {
        if (null !== $condition && ! is_array($condition)) {
            $condition = array($condition);
        }

        $this->conditions = $condition;
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    public function hasConditions()
    {
        return null !== $this->conditions;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param array $params
     */
    public function setParameters($params)
    {
        $this->parameters = $params;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
