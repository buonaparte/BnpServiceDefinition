BnpServiceDefinition
====================

[![Build Status](https://travis-ci.org/buonaparte/BnpServiceDefinition.svg?branch=master)](https://travis-ci.org/buonaparte/BnpServiceDefinition)
[![Coverage Status](https://img.shields.io/coveralls/buonaparte/BnpServiceDefinition.svg)](https://coveralls.io/r/buonaparte/BnpServiceDefinition?branch=master)
[![Latest Stable Version](https://poser.pugx.org/buonaparte/bnp-service-definition/v/stable.svg)](https://packagist.org/packages/buonaparte/bnp-service-definition)
[![Latest Unstable Version](https://poser.pugx.org/buonaparte/bnp-service-definition/v/unstable.svg)](https://packagist.org/packages/buonaparte/bnp-service-definition)

This module allows you define ServiceManager factories through simple, yet verbose configuration.

Installation
------------

### Setup
1. Add this project to your composer.json:

    ``` json
    "require": {
        "buonaparte/bnp-service-definition": "1.*"
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

In a default ZF2 Application you will define
all dependencies through Factories, writing a Factory class for each service ofter takes a lot of time and becomes an
additional overhead, for fast prototyping developers usually define Factories using Closures - the problem with Closures,
is they cannot be cached. Many of Zf2 developer use `Zend\Di` for prototyping, however this one comes with even bigger
overhead, besides too much magic.
**BnpServiceDefinition** propose an alternative way of defining Factories, through all "beloved" array configuration,
right in your module config.
All definitions are represented by arrays, following the bellow structure (we will use short array syntax here, but this
module has no dependency for PHP 5.4):

```php
return [
    // ...
    'service_manager' => [
        // ...
        'definitions' => [
            'MovieLister' => [
                'class' => 'MyApp\Service\MovieLister',
                'arguments' => [
                    ['type' => 'service', 'value' => 'MovieFinder']
                ],
                'method_calls' => [
                    ['name' => 'setListingBehaviour', 'parameters' => ['default']]
                ]
            ],
            'MovieFinder' => [
                'class' => 'MyApp\Service\MovieFinder',
                'arguments' => [
                    ['type' => 'service', 'value' => 'MoviesTable']
                ]
            ],
            'MoviesTable' => [
                'class' => 'Zend\Db\TableGateway\TableGateway',
                'arguments' => [
                    'movies',
                    ['type' => 'service', 'value' => 'Zend\Db\Adapter'],
                    null,
                    ['type' => 'service', 'value' => 'MoviesResultSet']
                ]
            ],
            'MoviesResultSet' => [
                'class' => 'Zend\Db\ResultSet\HydratingResultSet',
                'arguments' => [
                    ['type' => 'service', 'value' => 'ClassMethodsHydrator'],
                    ['type' => 'service', 'value' => 'MovieEntityPrototype'],
                ]
            ]
        ],
        'invokables' => [
            'ClassMethodsHydrator' => 'Zend\Stdlib\Hydrator\ClassMethods',
            'MovieEntityPrototype' => 'MyApp\Entity\MovieEntity'
        ],
        'shared' => [
            'MovieEntityPrototype' => false
        ]
    ]
];
```

The above example illustrates a pretty simple `MovieLister` service definition. Notice an additional key under your
`service_manager` configuration. Each service definition can contain the following:

* **class** - the service class name
* **arguments** - arguments to pass to the constructor of the service, so called "hard dependencies"
* **method_calls** - any additional method calls on the service before returning, like setter injection or initialization
tasks

The MovieLister service is an instance of `MyApp\Service\MovieLister` having one single constructor argument with a
pretty strange syntax, an array `['type' => 'service', 'value' => 'MovieFinder']`, this tells the definition parser
to look for a `MovieFinder` instance in the Applications service locator (this is called a *Definition Parameter*).

A definition parameter is a simple string or a an array containing 2 entries: `type` and `value`. Parameters are used to
specify a service class name, constructor arguments, method names to call as well as it's parameters and conditions.
By default BnpServiceDefinition comes with these resolvable parameter types:

* **config** - takes a configuration value, specified by the `value` from the `Config` shared service, `value` can be
either a string or an array pointing to a nested config, ex: `['parameters', 'some_parameter']` will return
`$config['parameters']['some_parameter']` or `null` if configuration value could not be found.

* **service** - pulls a service by name, specified by the `value` from the ServiceManager, or null if the service is not defined
or could not be created, ex: `'Zend\Log'` will return `$serviceLocator->get('Zend\Log')` instance.

* **value** - passes the parameter **as it is**, defined under the `value` key, only `int`, `float` / `double`,
`boolean` and `array` are accepted. **!!! Notice**, if you want to pass an array as a parameter, you *must* use FQ form:
`['type' => 'value', 'value' => ['my_array_elements']]`.

* **dsl** - interprets the expression under the `value` key, the expression must be a valid
[Symfony Expression Language](http://symfony.com/doc/current/components/expression_language/index.html) statement.

Every parameter gets compiled to the `dsl` type form by `BnpServiceDefinition\Service\ParameterResolver`, to evaluate or
compile `config` and `service` types, for this purpose, the
[Symfony Expression Language](http://symfony.com/doc/current/components/expression_language/index.html) is extended with
2 functions:

```
service(service_name, silent = false, instance = null)
config(string_or_array_for_nested_config_path, silent = true, type = null)
```

Supposing the MovieLister behaviour will be retrieved from database, the `method_call` definition could become:

```php
// ...
'method_calls' => [
    [
        'name' => 'setListingBehaviour',
        'parameters' => [
            ['type' => 'dsl', 'value' => 'service("PreferencesMapper").getDefaultListingBehaviour()']
        ]
    ]
]
```

Method calls also support conditions, so this the method will be called if all conditions will be evaluated to true,
each condition is a *Definition Parameter* as well, this way the bellow is absolutely legal:

```php
// ...
'method_calls' => [
    [
        'name' => 'setListingBehaviour',
        'parameters' => [
            ['type' => 'dsl', 'value' => 'service("PreferencesMapper").getDefaultListingBehaviour()']
        ],
        'conditions' => [
            ['type' => 'dsl', 'value' => 'service("UserSession").hasDefaultListingSpecified()']
        ]
    ]
]
```

There are many cases when some of our services has the same constructor arguments, or part of them is the same. Because
using Abstract Factories could not be the right choice or is simply impossible, you can define the repeating Service Factory
stuff as an `abstract` definition, and all concrete factories will specify it as `parant` (parents are resolved recursively):

```php
'definitions' => [
    'DbAdapterDependentService' => [
        'arguments' => [
            ['type' => 'service', 'value' => 'Zend\Db\Adapter']
        ],
        'abstract' => true,
        // suppose all of them will implement Zend\Stdlib\InitializableInterface
        'method_calls' => [
            'init'
        ]
    ],
    'UserMapper' => [
        'class' => 'MyApp\Mapper\UserMapper',
        'parent' => 'DbAdapterDependentService'
    ],
    'SettingsMapper' => [
        'class' => 'MyApp\Mapper\SettingsMapper',
        'parent' => 'DbAdapterDependentService',
        'arguments' => [
            ['type' => 'config', 'value' => 'a_config_value', 'order' => -1]
        ]
    ]
]
```

Notice `order` key for parameters, this is optional and by default all parameters are given the order of `1`, however,
at the compile time, all arguments are sorted in ascending order of this key value, `SettingsMapper`s first constructor
argument will be a value pulled from the config.

Using definitions from PluginManager scopes
-------------------------------------------

You can add `definitions` support for each Plugin Manager, by specifying it in `definition-aware-containers` under
`bnp-service-definition` configuration key, Ex:

```php
'bnp-service-definition' => [
    'definition-aware-containers' => [
        'ControllerManager' => 'controller_manager',
    ]
]
```

**!!! Notice** service type parameters or service dsl function using from this scopes will point to the ZF2's Application
Service Manager, to access a plugin from current scope you can use this dsl syntax:
`service('ControllerManager').get('some_service')`.

Supposing a `MoviesController`, we can now inject the `MovieLister` service as a hard dependency:

```php
'controller_manager' => [
    'definitions' => [
        'MoviesController' => [
            'class' => 'MyApp\Controller\MoviesController',
            'arguments' => [
                ['type' => 'service', 'value' => 'MovieLister']
            ]
        ]
    ]
]
```

How it works
------------

During Application bootstrap event, `BnpServiceDefinition` module registers an additional Abstract Factory to the Application's ServiceManager,
at the same time, `definition-aware-containers` under `bnp-service-definition` configuration key is read and an Abstract Factory instance is registered
for each of containers specified.
The Abstract Factory will look for `definitions` key under ServiceManager configuration key it belongs to and is responsible to create on the fly or delegate
the creation to a compiled version of all "terminal" (do not contain `'abstract' => true`) definitions.

If `dump-abstract-factories` under `bnp-service-definition` is set to `true`, The Abstract Factory will delegate all it's calls to the compiled (dumped) version,
or each requested definition will be compiled to [Symfony Expression Language](http://symfony.com/doc/current/components/expression_language/index.html) and evaluated on the fly otherwise.

For performance considerations you will always use `dump-abstract-factories` set to true, the module will check if your definitions have changed and
regenerate the compiled version on the fly, all you will care about is specify a writable directory for storing that abstract factories, ex: `./data/bnp-service-definitions`

A dumped version for the `MovieLister` service example will be generated in a file `BnpGeneratedAbstractFactory_a81f0487f49ba10e22972a55497525bc.php` with this content:

```php
/**
 * Generated by BnpServiceDefinition\Service\Generator (at 10:54 14-07-2014)
 */
class BnpGeneratedAbstractFactory_a81f0487f49ba10e22972a55497525bc implements \Zend\ServiceManager\AbstractFactoryInterface, \Zend\ServiceManager\ServiceLocatorAwareInterface
{

    /**
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $services = null;

    /**
     * @var string
     */
    protected $scopeLocatorName = null;

    /**
     * Constructor
     *
     * @param string $scopeLocatorName
     */
    public function __construct($scopeLocatorName = null)
    {
        $this->scopeLocatorName = $scopeLocatorName;
    }

    /**
     * Determine if we can create a service with name
     *
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @param string $name
     * @param string $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return in_array($requestedName, array('MovieLister', 'MovieFinder', 'MoviesTable', 'MoviesResultSet'));
    }

    /**
     * Create service with name
     *
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @param string $name
     * @param string $requestedName
     * @return mixed
     */
    public function createServiceWithName(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        switch ($requestedName) {

            case 'MovieLister':
                return $this->getMovieLister('MovieLister');
            case 'MovieFinder':
                return $this->getMovieFinder('MovieFinder');
            case 'MoviesTable':
                return $this->getMoviesTable('MoviesTable');
            case 'MoviesResultSet':
                return $this->getMoviesResultSet('MoviesResultSet');
        }

        return null;
    }

    /**
     * Set service locator
     *
     * @param Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * Get service locator
     *
     * @return \Zend\ServiceManager\ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->services;
    }

    /**
     * Returns the service registered under "MovieLister" definition
     *
     * @param string $definitionName
     * @return object
     * @throws \BnpServiceDefinition\Exception\RuntimeException If an error occurs
     * during instantiation
     */
    protected function getMovieLister($definitionName)
    {
        set_error_handler(
            function ($level, $message) use ($definitionName) {
                throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
                    'A %d level error occurred (message: "%s") while creating %s service from compiled Abstract Factory',
                    $level,
                    $message,
                    $definitionName
                ));
            }
        );

        $serviceClassName = "MyApp\\Service\\MovieLister";
        if (! is_string($serviceClassName)) {
            throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
                '%s definition class was not resolved to a string',
                $definitionName
            ));
        }
        if (! class_exists($serviceClassName, true)) {
            throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
                '%s definition resolved to the class %s, which does no exit',
                $definitionName,
                $serviceClassName
            ));
        }
        $serviceReflection = new \ReflectionClass($serviceClassName);
        $service = $serviceReflection->newInstanceArgs(array($this->services->get('BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider')->getService("MovieFinder", false, null)));


        if (true) {
            $serviceMethod = "setListingBehaviour";
            if (! is_string($serviceMethod)) {
                throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
                    'A method call can only be a string, %s provided, as %d method call for the %s service definition',
                    gettype($serviceMethod),
                    0,
                    $definitionName
                ));
            } elseif (! method_exists($service, $serviceMethod)) {
                throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
                    'Requested method "%s::%s" (index %d) does not exists or is not visible for %s service definition',
                    get_class($service),
                    $serviceMethod,
                    0,
                    $definitionName
                ));
            }

            call_user_func_array(
                array($service, $serviceMethod),
                array("default")
            );
        }

        restore_error_handler();

        return $service;
    }

    /**
     * Returns the service registered under "MovieFinder" definition
     *
     * @param string $definitionName
     * @return object
     * @throws \BnpServiceDefinition\Exception\RuntimeException If an error occurs
     * during instantiation
     */
    protected function getMovieFinder($definitionName)
    {
        set_error_handler(
            function ($level, $message) use ($definitionName) {
                throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
                    'A %d level error occurred (message: "%s") while creating %s service from compiled Abstract Factory',
                    $level,
                    $message,
                    $definitionName
                ));
            }
        );

        $serviceClassName = "MyApp\\Service\\MovieFinder";
        if (! is_string($serviceClassName)) {
            throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
                '%s definition class was not resolved to a string',
                $definitionName
            ));
        }
        if (! class_exists($serviceClassName, true)) {
            throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
                '%s definition resolved to the class %s, which does no exit',
                $definitionName,
                $serviceClassName
            ));
        }
        $serviceReflection = new \ReflectionClass($serviceClassName);
        $service = $serviceReflection->newInstanceArgs(array($this->services->get('BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider')->getService("MoviesTable", false, null)));

        restore_error_handler();

        return $service;
    }

    /**
     * Returns the service registered under "MoviesTable" definition
     *
     * @param string $definitionName
     * @return object
     * @throws \BnpServiceDefinition\Exception\RuntimeException If an error occurs
     * during instantiation
     */
    protected function getMoviesTable($definitionName)
    {
        set_error_handler(
            function ($level, $message) use ($definitionName) {
                throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
                    'A %d level error occurred (message: "%s") while creating %s service from compiled Abstract Factory',
                    $level,
                    $message,
                    $definitionName
                ));
            }
        );

        $serviceClassName = "Zend\\Db\\TableGateway\\TableGateway";
        if (! is_string($serviceClassName)) {
            throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
                '%s definition class was not resolved to a string',
                $definitionName
            ));
        }
        if (! class_exists($serviceClassName, true)) {
            throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
                '%s definition resolved to the class %s, which does no exit',
                $definitionName,
                $serviceClassName
            ));
        }
        $serviceReflection = new \ReflectionClass($serviceClassName);
        $service = $serviceReflection->newInstanceArgs(array($this->services->get('BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider')->getService("MoviesResultSet", false, null), null, $this->services->get('BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider')->getService("Zend\\Db\\Adapter", false, null), "movies"));

        restore_error_handler();

        return $service;
    }

    /**
     * Returns the service registered under "MoviesResultSet" definition
     *
     * @param string $definitionName
     * @return object
     * @throws \BnpServiceDefinition\Exception\RuntimeException If an error occurs
     * during instantiation
     */
    protected function getMoviesResultSet($definitionName)
    {
        set_error_handler(
            function ($level, $message) use ($definitionName) {
                throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
                    'A %d level error occurred (message: "%s") while creating %s service from compiled Abstract Factory',
                    $level,
                    $message,
                    $definitionName
                ));
            }
        );

        $serviceClassName = "Zend\\Db\\ResultSet\\HydratingResultSet";
        if (! is_string($serviceClassName)) {
            throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
                '%s definition class was not resolved to a string',
                $definitionName
            ));
        }
        if (! class_exists($serviceClassName, true)) {
            throw new \BnpServiceDefinition\Exception\RuntimeException(sprintf(
                '%s definition resolved to the class %s, which does no exit',
                $definitionName,
                $serviceClassName
            ));
        }
        $serviceReflection = new \ReflectionClass($serviceClassName);
        $service = $serviceReflection->newInstanceArgs(array($this->services->get('BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider')->getService("MovieEntityPrototype", false, null), $this->services->get('BnpServiceDefinition\Dsl\Extension\ServiceFunctionProvider')->getService("ClassMethodsHydrator", false, null)));

        restore_error_handler();

        return $service;
    }
}
```