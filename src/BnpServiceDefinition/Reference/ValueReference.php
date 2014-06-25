<?php

namespace BnpServiceDefinition\Reference;

use BnpServiceDefinition\Dsl\LanguageUtils;
use Zend\Stdlib\ArrayUtils;

class ValueReference implements ReferenceInterface
{
    /**
     * @return string
     */
    public static function getType()
    {
        return 'value';
    }

    /**
     * @param $definition array|string
     * @return string BnpServiceDefinition\Dsl\Language compatible
     * @throws Exception\InvalidArgumentException for unsupported types
     */
    public function compile($definition)
    {
        switch (gettype($definition)) {
            case 'string':
                $definition = LanguageUtils::escapeSingleQuotedString($definition);
                return "'$definition'";

            case 'int':
            case 'integer':
                return $definition;

            case 'float':
            case 'double':
                $definition = (string) $definition;
                if (false === strpos($definition, '.')) {
                    $definition .= '.0';
                }

                return $definition;

            case 'array':
                // PHP 5.3 compatibility
                $self = $this;

                if (ArrayUtils::isHashTable($definition)) {
                    return '{'
                        . implode(', ', array_map(
                            function ($key) use ($self, $definition) {
                                return sprintf(
                                    "'%s': %s",
                                    LanguageUtils::escapeSingleQuotedString($key),
                                    $self->compile($definition[$key])
                                );
                            },
                            array_keys($definition)))
                        . '}';
                } else {
                    return '['
                        . implode(', ', array_map(
                            function ($value) use ($self) { return $self->compile($value); },
                            $definition
                        ))
                        . ']';
                }

            case 'bool':
            case 'boolean':
                return $definition ? 'true' : 'false';
        }

        throw new Exception\InvalidArgumentException(sprintf(
            'Unsupported type "%s", %s can only compile these types: (%s)',
            gettype($definition),
            get_class($this),
            implode(', ', array('string', 'int', 'float', 'array'))
        ));
    }
}