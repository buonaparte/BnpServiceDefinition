<?php

namespace BnpServiceDefinition\Dsl\Extension\Feature;

interface FunctionProviderInterface
{
    public function getName();

    public function getEvaluator(array $context = array());

    public function getCompiler();
}
