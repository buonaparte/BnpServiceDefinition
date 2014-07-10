<?php

return array(
    'service_manager' => array(
        'invokables' => array(
            \BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider::SERVICE_KEY =>
                'BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider',
            \BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider::SERVICE_KEY =>
                'BnpServiceDefinition\Dsl\Extension\ConfigFunctionProvider',
            'BnpServiceDefinition\Factory\DefinitionAbstractFactory' =>
                'BnpServiceDefinition\Factory\DefinitionAbstractFactory',
            'BnpServiceDefinition\Service\ParameterResolver' => 'BnpServiceDefinition\Service\ParameterResolver'
        ),
        'factories' => array(
            'BnpServiceDefinition\Service\Evaluator' => 'BnpServiceDefinition\Factory\EvaluatorFactory',
            'BnpServiceDefinition\Service\Generator' => 'BnpServiceDefinition\Factory\GeneratorFactory',
            'BnpServiceDefinition\Dsl\Language' => 'BnpServiceDefinition\Factory\LanguageFactory',
            'BnpServiceDefinition\Options\DefinitionOptions' => 'BnpServiceDefinition\Factory\DefinitionOptionsFactory',
        )
    )
);