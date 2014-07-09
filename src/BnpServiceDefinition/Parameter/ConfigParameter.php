<?php

namespace BnpServiceDefinition\Parameter;

use BnpServiceDefinition\Dsl\LanguageUtils;
use BnpServiceDefinition\Exception;
use Zend\Stdlib\ArrayUtils;

class ConfigParameter implements ParameterInterface
{
    /**
     * @param $definition array|\Traversable
     * @return string BnpServiceDefinition\Dsl\Language compatible
     * @throws Exception\InvalidArgumentException if not an non-empty array provided
     */
    public function compile($definition)
    {
        if ($definition instanceof \Traversable) {
            $definition = ArrayUtils::iteratorToArray($definition);
        } elseif (is_string($definition)) {
            $definition = array($definition);
        }

        if (! is_array($definition) || empty($definition)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s can compile only an non empty config path array',
                get_class($this)
            ));
        }

        $self = $this;
        array_walk($definition, function ($part) use ($self) {
            if (! is_string($part)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    '%s can only compile a valid config (array of string parts or string),
                        %s provided as an array part',
                    get_class($self),
                    gettype($part)
                ));
            }
        });

        return sprintf(
            'config([\'%s\'])',
            implode(
                '\', \'',
                array_map(
                    function ($part) {
                        return LanguageUtils::escapeSingleQuotedString($part);
                    },
                    $definition
                )
            )
        );
    }
}
