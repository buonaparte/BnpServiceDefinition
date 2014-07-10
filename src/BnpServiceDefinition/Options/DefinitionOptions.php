<?php

namespace BnpServiceDefinition\Options;

use Zend\Stdlib\AbstractOptions;

class DefinitionOptions extends AbstractOptions
{
    protected $__strictMode__ = false;

    /**
     * @var array
     */
    protected $definitionAwareContainers = array();

    /**
     * @var bool
     */
    protected $dumpFactories = false;

    /**
     * @var string
     */
    protected $dumpDirectory;

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
