<?php

namespace BnpServiceDefinition\Reference;

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
                return "'$definition'";

            case 'int':
            case 'float':
                return $definition;

            case 'array':
                // PHP 5.3 compatibility
                $self = $this;

                if (ArrayUtils::isHashTable($definition)) {
                    return '{'
                        . implode(', ', array_map(
                            function ($key) use ($self, $definition) {
                                return "'$key': {$self->compile($definition[$key])}";
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
        }

        throw new Exception\InvalidArgumentException(sprintf(
            'Unsupported type "%s", %s can only compile these types: (%s)',
            gettype($definition),
            get_class($this),
            implode(', ', array('string', 'int', 'float', 'array'))
        ));
    }
}