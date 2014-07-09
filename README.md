BnpServiceDefinition
====================

This module allows you define ServiceManager factories through simple and verbose configuration.

Installation
------------

### Setup
1. Add this project to your composer.json:

    ``` json
    "require": {
        "buonaparte/bnp-service-definition": "dev-master"
    }
    ```

2. Now tell composer to download BnpServiceDefinition by running the command:

    ``` bash
    $ php composer.phar update
    ```

### Post installation

Enabling it in your `application.config.php` file.

``` php
<?php
return array(
    'modules' => array(
        // ...
        'BnpServiceDefinition',
    ),
    // ...
);
```


Configuration
-------------

Configure the module, by copying and adjusting `config/bnp-service-definition.global.php.dist` to your config include path.

Definition
----------

All definitions are represented by arrays, following the bellow structure (we will use short array syntax here, but this module
has no dependency for PHP 5.4):

```php
return [
    // ...
    'service_manager' => [
        // ...
        'definitions' => [
            'salary_computer_service' => [
                'class' => 'Me\\Service\\SalaryComputerService',
                'arguments' => ['default_computer_strategy'],
                'method_calls' => [
                    [
                        'name' => 'setMonthlyBudget',
                        'parameters' => [1039.00]
                    ]
                ]
            ]
        ]
    ]
];
```

This does not bring much flexibility, as we use Service Factories for more complex task, such as injecting other services,
not providing strings as service constructor parameters and we do not want to hard - code the budget value in the method call
definition, thus, `class`, `arguments`, method calls `parameters` accept so called **Definition parameters**.

### Definition Parameter

A definition parameter is a simple string or a an array containing 2 entries: `type` and `value`. Parameters are used to
specify a service class name, constructor arguments, method names to call as well as it's parameters and conditions.
By default BnpServiceDefinition comes with this resolvable parameter types:

* `config` - takes a configuration value, specified by the `value` from the `Config` shared service, `value` can be
either a string or an array pointing to a nested config, ex: ['parameters', 'some_parameter'] will return `$config['parameters']['some_parameter']` or `null`
if configuration value could not be found.

* `service` - pulls a service by name, specified by the `value` from the ServiceManager, or null if the service is not defined
or could not be created, ex: 'Zend\Log' will return $serviceLocator->get('Zend\Log') instance.

* `value` - passes the parameter **as it is**, defined under the `value` key, only `int`, `float` / `double`, `boolean` and `array` are accepted.

* `dsl` - interprets the expression under the `value` key, the expression must be a valid Symfony Expression Language statement.

Now the above definition could become:

```php
return [
    'some_nested_config' => [
        'salary_computer.class' => 'Me\\Service\\SalaryComputerService',
    ],
    'monthly_budget_config_value' => 1039.00,
    'service_manager' => [
        'aliases' => [
            'salary_computer_strategy' => 'Me\\Service\\SalaryComputerStrategy'
        ],
        'invokables' => [
            'Me\\Service\\SalaryComputerStrategy' => 'Me\\Service\\SalaryComputerStrategy'
        ],
        'definitions' => [
            'salary_computer_service' => [
                'class' => ['type' => 'config', 'value' => ['some_nested_config', 'salary_computer.class']],
                'arguments' => [
                    ['type' => 'service', 'value' => 'salary_computer_strategy']
                ],
                'method_calls' => [
                    'name' => 'setMonthlyBudget',
                    'parameters' => ['type' => 'config', 'value' => 'monthly_budget_config_value']
                ]
            ]
        ]
    ]
];
```

Every parameter gets compiled to the `dsl` type form by `BnpServiceDefinition\Service\ParameterResolver`, to evaluate or
compile `config` and `service` types, the Expression Language is extended with 2 functions:

```
service(service_name, silent = false, instance = null)
config(string_or_array_for_nested_config_path, silent = true, type = null)
```

Supposing the monthly budget could be retrieved from the database, wrapped in another service the definition could become

```php
// ...
'method_calls' => [
    'name' => [
        'name' => 'setMonthlyBudget',
        'parameters' => ['type' => 'dsl', 'value' => 'service("budget_repository").getMonthlyBudget()']
    ]
],
// ...
```

There are many cases when some of our services has the same constructor arguments, or part of them is the same. Because
using Abstract Factories could not be the right choice or is simply impossible, you can define the repeating Service Factory
stuff as an `abstract` definition, and all concrete factories will specify it as `parant` (parents are resolved recursively):

```php
'definitions' => [
    'db_adapter_dependent_service' => [
        'arguments' => [
            ['type' => 'service', 'value' => 'Zend\Db\Adapter']
        ],
        'abstract' => true,
        // suppose all of them will implement Zend\Stdlib\InitializableInterface
        'method_calls' => [
            'init'
        ]
    ],
    'user_mapper' => [
        'class' => 'Me\\Mapper\\UserMapper',
        'parent' => 'db_adapter_dependent_service'
    ],
    'setting_mapper' => [
        'class' => 'Me\\Mapper\\SettingsMapper',
        'parent' => 'db_adapter_dependent_service',
        'arguments' => [
            ['type' => 'config', 'value' => 'a_config_value', 'order' => -1]
        ]
    ]
]
```

Notice `order` key for parameters, this is optional and by default all parameters are given the order of `1`, however,
at the compile time, all arguments are sorted in ascending order of this key value, `settings_mapper` first constructor argument
will be a value pulled from the config.

Using definitions from PluginManager scopes
-------------------------------------------

You can add `definition` support for each Plugin Manager, by specifying it in `definition-aware-containers` under `bnp-service-definition` configuration key,
Ex:

```php
return [
    'bnp-service-definition' => [
        'definition-aware-containers' => [
            'ControllerManager' => 'controller_manager',
            'FormElementManager' => 'form_manager'
        ]
    ]
];
```

**Notice** service type parameters or service dsl function using from this scopes will point to the Zend Framework's root Service Manager,
to access a plugin from current scope you can use this dsl syntax: `service('ControllerManager').get(some_service)`.

How it works
------------

During Application bootstrap event, `BnpServiceDefinition` module registers an additional Abstract Factory to the Application's ServiceManager,
at the same time, `definition-aware-containers` under `bnp-service-definition` configuration key is read and an Abstract Factory instance is registered
for each of containers specified.
The Abstract Factory will look for `definitions` key under ServiceManager configuration key it belongs to and is responsible to create on the fly or delegate
the creation to a compiled version of all "terminal" (do not contain `'abstract' => true`) definitions.

If `dump-abstract-factories` under `bnp-service-definition` is set to `true`, The Abstract Factory will delegate all it's calls to the compiled (dumped) version,
or each requested definition will be compiled to Symfony Expression Language and evaluated on the fly otherwise.

For performance considerations you will always use `dump-abstract-factories` set to true, the module will check if your definitions have changed and
regenerate the compiled version on the fly, all you will care about is specify a writable directory for storing that abstract factories, ex: `./data/bnp-service-definitions`

Example of a dumped abstract factory:

