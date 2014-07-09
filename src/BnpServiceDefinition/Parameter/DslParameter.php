<?php

namespace BnpServiceDefinition\Parameter;

class DslParameter implements ParameterInterface
{
    /**
     * @param $definition array|string
     * @return string BnpServiceDefinition\Dsl\Language compatible
     */
    public function compile($definition)
    {
        return $definition;
    }
}
