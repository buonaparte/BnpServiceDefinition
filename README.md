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

How it works
------------

During Application bootstrap event, `BnpServiceDefinition` module registers an additional Abstract Factory to the Application's ServiceManager,
at the same time, `definition-aware-containers` under `bnp-service-definition` configuration key is read and an Abstract Factory instance is registered
for each of containers specified.
The Abstract Factory will look for `definitions` key under ServiceManager configuration key it belongs to and is responsible to create on the fly or delegate
the creation to a compiled version of all "terminal" (do not contain `'abstract' => true`) definitions.

If `dump-abstract-factories` under `bnp-service-definition` is set to `true`, The Abstract Factory will delegate all it's calls to the compiled (dumped) version,
each requested definition will be compiled to Symfony Expression Language and evaluated on the fly otherwise.

Definition
----------

### Syntax

A service definition has the following syntax:

```
service_name:
    class: {parameter},
    arguments|args: An array of {parameter}s
    method_calls|calls|methodCalls: An array of {methodCall}s sub-definitions, empty by default
    parent: string (the name of the parent definition being extended), null by default
    abstract: boolean (specifies if this definition is terminal, or will be used only as a template for others, common injections)
```

a `{methodCall}` will be defined as follows:

```
name: {parameter}
params|parameters: An array of {parameter}s
condition|conditions: {parameter} or an array of {parameters}s, which will be evaluated in order using AND operator
```

An alternative, short-cut syntax for method calls is supported as well:

```
[{parameter} representing name, [{parameter}s representing arguments], [{params}s representing conditions]]
```

In this case method call definition keys are omitted, the only required argument is the method name, in cases when the method
being invoked does not require any parameters and there is no need to do some decisions based on conditions, the method call can
be reduces to a string, representing the name of the method to invoke.

Definition Parameter
--------------------

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


Factory definitions
-------------------

### Constructor injection

```php
'some_service' => [
    'class' => 'ServiceFQClassName',
    'arguments' => ['a_string_argument']
]

Usage
-----

```php
$ffmpeg = $serviceLocator->get('FFMpeg');

// Open video
$video = $ffmpeg->open('/your/source/folder/input.avi');

// Resize to 720x480
$video
    ->filters()
    ->resize(new Dimension(720, 480), ResizeFilter::RESIZEMODE_INSET)
    ->synchronize();

// Start transcoding and save video
$video->save(new X264(), '/your/target/folder/video.mp4');
```
