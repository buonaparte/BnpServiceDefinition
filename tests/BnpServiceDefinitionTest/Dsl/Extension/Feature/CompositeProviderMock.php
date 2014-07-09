<?php

namespace BnpServiceDefinitionTest\Dsl\Extension\Feature;

use BnpServiceDefinition\Dsl\Extension\Feature\ContextVariablesProviderInterface;
use BnpServiceDefinition\Dsl\Extension\Feature\FunctionProviderInterface;

abstract class CompositeProviderMock implements
    ContextVariablesProviderInterface,
    FunctionProviderInterface
{
}
