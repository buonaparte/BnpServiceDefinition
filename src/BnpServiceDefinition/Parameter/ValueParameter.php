<?php

namespace BnpServiceDefinition\Parameter;

use BnpServiceDefinition\Dsl\LanguageUtils;
use Zend\Stdlib\ArrayUtils;
use BnpServiceDefinition\Exception;

class ValueParameter implements ParameterInterface
{
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
                $self = $this;
                $ret = null;
                if (ArrayUtils::isHashTable($definition)) {
                    $ret = '{'
                        . implode(
                            ', ',
                            array_map(
                                function ($key) use ($self, $definition) {
                                    return sprintf(
                                        "'%s': %s",
                                        LanguageUtils::escapeSingleQuotedString($key),
                                        $self->compile($definition[$key])
                                    );
                                },
                                array_keys($definition)
                            )
                        )
                        . '}';
                } else {
                    $ret = '['
                        . implode(', ', array_map(
                            function ($value) use ($self) {
                                return $self->compile($value);
                            },
                            $definition
                        ))
                        . ']';
                }

                return $ret;

            case 'bool':
            case 'boolean':
                return $definition ? 'true' : 'false';

            case 'NULL':
                return 'null';
        }

        throw new Exception\InvalidArgumentException(sprintf(
            'Unsupported type "%s", %s can only compile these types: (%s)',
            gettype($definition),
            get_class($this),
            implode(', ', array('string', 'int', 'float', 'array'))
        ));
    }
}
