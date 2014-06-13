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
}