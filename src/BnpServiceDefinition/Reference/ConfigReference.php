<?php

namespace BnpServiceDefinition\Reference;

class ConfigReference implements ReferenceInterface
{
    /**
     * @return string
     */
    public static function getType()
    {
        return 'config';
    }

    /**
     * @param $definition array|string
     * @return string BnpServiceDefinition\Dsl\Language compatible
     */
    public function compile($definition)
    {
        return "config('$definition')";
    }
}