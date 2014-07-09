<?php

namespace BnpServiceDefinition\Dsl;

class LanguageUtils
{
    public static function escapeSingleQuotedString($string)
    {
        $string = preg_replace("#(^|[^\\\\])'#", "$1\\'", $string);
        return str_replace('\\', '\\\\', $string);
    }
}
