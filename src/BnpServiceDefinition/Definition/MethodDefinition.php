<?php

namespace BnpServiceDefinition\Definition;

use Zend\Stdlib\Hydrator\ClassMethods;

class MethodDefinition
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var string|array
     */
    protected $condition;

    public function __construct($name, array $params = array(), $condition = null)
    {
        $this->setName($name);
        $this->setParams($params);
        $this->setCondition($condition);
    }

    /**
     * @param array $specs
     * @return MethodDefinition
     * @throws \RuntimeException
     */
    public static function fromArray(array $specs)
    {
        if (! isset($specs['name'])) {
            throw new \RuntimeException('MethodDefinition expects at least a method name, under "name" key');
        }

        $hydrator = new ClassMethods();
        return $hydrator->hydrate($specs, new static(isset($specs['name']) ? $specs['name'] : null));
    }

    /**
     * @param array|string $condition
     */
    public function setCondition($condition)
    {
        if (null !== $condition && ! is_array($condition)) {
            $condition = array($condition);
        }

        $this->condition = $condition;
    }

    /**
     * @return array
     */
    public function getCondition()
    {
        return $this->condition;
    }

    public function hasCondition()
    {
        return null !== $this->condition;
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
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }
}