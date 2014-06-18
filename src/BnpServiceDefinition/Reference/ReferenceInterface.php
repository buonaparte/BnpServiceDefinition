<?php

namespace BnpServiceDefinition\Reference;

interface ReferenceInterface
{
    /**
     * @return string
     */
    public static function getType();

    /**
     * @param $definition array|string
     * @return string BnpServiceDefinition\Dsl\Language compatible
     */
    public function compile($definition);
}