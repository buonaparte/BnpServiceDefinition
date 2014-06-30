<?php

namespace BnpServiceDefinition\Options;

use Zend\Stdlib\AbstractOptions;

class DefinitionOptions extends AbstractOptions
{
    protected $__strictMode__ = false;

    /**
     * @var string
     */
    protected $configPathSeparator = ':';

    /**
     * @var array
     */
    protected $definitionAwareContainers;

    /**
     * @var bool
     */
    protected $dumpFactories = true;

    /**
     * @var string
     */
    protected $dumpDirectory;

    /**
     * @var string
     */
    protected $dumpedAbstractFactoriesNamespace;

    /**
     * @param string $configPathSeparator
     */
    public function setConfigPathSeparator($configPathSeparator)
    {
        $this->configPathSeparator = $configPathSeparator;
    }

    /**
     * @return string
     */
    public function getConfigPathSeparator()
    {
        return $this->configPathSeparator;
    }

    /**
     * @param array $definitionAwareContainers
     */
    public function setDefinitionAwareContainers($definitionAwareContainers)
    {
        $this->definitionAwareContainers = $definitionAwareContainers;
    }

    /**
     * @return array
     */
    public function getDefinitionAwareContainers()
    {
        return $this->definitionAwareContainers;
    }

    /**
     * @param string $dumpDirectory
     */
    public function setDumpDirectory($dumpDirectory)
    {
        $this->dumpDirectory = $dumpDirectory;
    }

    /**
     * @return string
     */
    public function getDumpDirectory()
    {
        return $this->dumpDirectory;
    }

    /**
     * @param string $dumpedAbstractFactoriesNamespace
     */
    public function setDumpedAbstractFactoriesNamespace($dumpedAbstractFactoriesNamespace)
    {
        $this->dumpedAbstractFactoriesNamespace = $dumpedAbstractFactoriesNamespace;
    }

    /**
     * @return string
     */
    public function getDumpedAbstractFactoriesNamespace()
    {
        return $this->dumpedAbstractFactoriesNamespace;
    }

    /**
     * @param boolean $dumpFactories
     */
    public function setDumpFactories($dumpFactories)
    {
        $this->dumpFactories = $dumpFactories;
    }

    /**
     * @return boolean
     */
    public function getDumpFactories()
    {
        return $this->dumpFactories;
    }
}