<?php

namespace BnpServiceDefinition\Reference;

class ServiceReference implements ReferenceInterface
{
    /**
     * @return string
     */
    public static function getType()
    {
        return 'service';
    }

    /**
     * @param $definition array|string
     * @return string BnpServiceDefinition\Dsl\Language compatible
     */
    public function compile($definition)
    {
        return "service('$definition')";
    }
}