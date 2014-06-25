<?php

namespace BnpServiceDefinition\Reference;

use BnpServiceDefinition\Dsl\LanguageUtils;
use BnpServiceDefinition\Reference\Exception\InvalidArgumentException;

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
     * @throws InvalidArgumentException
     */
    public function compile($definition)
    {
        if (! is_string($definition)) {
            throw new InvalidArgumentException(sprintf(
                '%s can only compile string values, %s provided',
                $this::getType(),
                gettype($definition)
            ));
        }

        $definition = LanguageUtils::escapeSingleQuotedString($definition);
        return "service('$definition')";
    }
}