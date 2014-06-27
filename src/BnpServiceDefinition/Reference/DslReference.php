<?php

namespace BnpServiceDefinition\Reference;

class DslReference implements ReferenceInterface
{
    /**
     * @return string
     */
    public static function getType()
    {
        return 'dsl';
    }

    /**
     * @param $definition array|string
     * @return string BnpServiceDefinition\Dsl\Language compatible
     */
    public function compile($definition)
    {
        return $definition;
    }
}