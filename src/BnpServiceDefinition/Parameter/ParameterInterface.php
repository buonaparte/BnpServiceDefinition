<?php

namespace BnpServiceDefinition\Parameter;

use BnpServiceDefinition\Exception;

interface ParameterInterface
{
    /**
     * @param $definition array|string
     * @return string BnpServiceDefinition\Dsl\Language compatible
     */
    public function compile($definition);
}
