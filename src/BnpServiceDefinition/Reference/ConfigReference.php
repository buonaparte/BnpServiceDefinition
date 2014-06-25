<?php

namespace BnpServiceDefinition\Reference;

use BnpServiceDefinition\Dsl\LanguageUtils;
use BnpServiceDefinition\Reference\Exception\InvalidArgumentException;
use Zend\Stdlib\ArrayUtils;

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
     * @throws InvalidArgumentException
     */
    public function compile($definition)
    {
        if ($definition instanceof \Traversable) {
            $definition = ArrayUtils::iteratorToArray($definition);
        }

        if (is_array($definition)) {
            if (empty($definition)) {
                throw new InvalidArgumentException(sprintf(
                    '%s can compile only an non empty config path array',
                    $this::getType()
                ));
            }

            $self = $this;
            array_walk($definition, function ($part) use ($self) {
                if (! is_string($part)) {
                    throw new InvalidArgumentException(sprintf(
                        '%s can only compile a valid config (array of string parts or string),
                            %s provided as an array part',
                        $self::getType(),
                        gettype($part)
                    ));
                }
            });

            return sprintf(
                'config([\'%s\'])',
                implode('\', \'', array_map(
                    function ($part) { return LanguageUtils::escapeSingleQuotedString($part); },
                    $definition)
                )
            );
        } elseif (is_string($definition)) {
            $definition = LanguageUtils::escapeSingleQuotedString($definition);
            return "config('$definition')";
        }

        throw new InvalidArgumentException(sprintf(
            '%s can only compile a valid config (array of string parts as path) or a string, %s provided',
            $this::getType(),
            gettype($definition)
        ));
    }
}