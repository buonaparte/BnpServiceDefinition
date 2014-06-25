<?php

namespace BnpServiceDefinition\Parameter;

interface ParameterInterface
{
    public static function fromArray(array $options = array());

    public function compile();
}