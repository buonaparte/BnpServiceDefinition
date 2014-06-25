<?php

namespace BnpServiceDefinition\Dsl;

class LanguageUtils
{
    public static function escapeSingleQuotedString($string)
    {
        return preg_replace("#(^|[^\\\\])'#", "$1\\'", $string);
    }

    public static function escapeDoubleQuotedString($string)
    {
        return preg_replace('#(^|[^\\])"#', '$1\"', $string);
    }
}