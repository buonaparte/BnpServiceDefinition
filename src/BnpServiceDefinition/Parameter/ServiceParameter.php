<?php

namespace BnpServiceDefinition\Parameter;

use BnpServiceDefinition\Dsl\LanguageUtils;
use BnpServiceDefinition\Exception;

class ServiceParameter implements ParameterInterface
{
    /**
     * @param $definition array
     * @return string BnpServiceDefinition\Dsl\Language compatible
     * @throws Exception\InvalidArgumentException if not an non-empty array provided
     */
    public function compile($definition)
    {
        if (! is_string($definition)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s can only compile string values, %s provided',
                get_class($this),
                gettype($definition)
            ));
        }

        $definition = LanguageUtils::escapeSingleQuotedString($definition);
        return "service('$definition')";
    }
}
