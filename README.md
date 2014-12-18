Dependency Injection Container for PHP
======================================

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/dennisbirkholz/di/master.svg?style=flat-square)](https://travis-ci.org/dennisbirkholz/di)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/dennisbirkholz/di.svg?style=flat-square)](https://scrutinizer-ci.com/g/dennisbirkholz/di/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/dennisbirkholz/di.svg?style=flat-square)](https://scrutinizer-ci.com/g/dennisbirkholz/di)

The idea
========

Dependency injection containers (for PHP) tend to be a combination of
- service locators that allow you get the instance of a (service) class
- generic factories that instantiate objects for you and inject all required dependencies
- config registries where you can store configuration data

There are several DI containers out there that solve more or less all of the above jobs but most often lack one thing: they have no sensible defaults.

The features
============

Get a service
-------------
The most basic feature is the use of convention over configuration:
instead of using fantasy names for your services, names are Interface or Class names.
As a result, if you request a service from the DI container that has no configuration, the container just tries to instantiate the class.

```php
// We use the $di variable from now on without instantiation in further examples
$di = \birkholz\di\DependencyContainer();

// Will create a new instance of the Monolog logger class
$logger = $di->get(\Monolog\Logger::class);

...

// Fetches the same instance again
$logger = $di->get(\Monolog\Logger::class);
```

Most of our classes will hopefully not be type-hinted against the Monolog logger directly.
Instead, they should be type-hinted against the ```\Psr\Log\LoggerInterface``` interface.
As an interface can not be instantiated, the container tries to guess a default implementation for the ```LoggerInterface```.
By stripping the "Interface" suffix, it tries to instantiate a ```\Psr\Log\DefaultLogger```, ```\Psr\Log\DefaultLoggerImpl``` or ```\Psr\Log\NullLogger``` class, in that order.
So your application can rely on using the ```LoggerInterface``` even if no rule for that interface has been specified.

```php
// Receives the default logger implementation (instance of \Psr\Log\NullLogger)
$logger = $di->get(\Psr\Log\LoggerInterface::class);
```

Rules for creating services are easy.
You can specify the name of the implementation to use:
```php
$di->uses(\Psr\Log\LoggerInterface::class, \Monolog\Logger::class);

// Receives an instance of the Monolog logger class
$logger = $di->get(\Psr\Log\LoggerInterface::class);
```

or you use your own factory callback method:
```php
$di->factory(\Psr\Log\LoggerInterface::class, function(\birkholz\di\DependencyContainer $di, $param1 = null) {
  return new \Monolog\Logger('default');
});

// Receives an instance of the Monolog logger class created by the factory method
$logger = $di->get(\Psr\Log\LoggerInterface::class);
```

Use as factory
--------------

In addition to receiving services, the DI container can be used as a generic factory that resolves all requirements your classes have.
Asuming you have the following class:

```php
class MyClass {
  public function __construct(Dependency1 $d1, Dependency2 $d2) { ... }
  public function setDependency2(Dependency2 $d2) { ... }
  public function setDependency3(Dependency3 $d3) { ... }
}
```

If you need a new instance of this class, you simply call:
```php
$instance = $di->create(MyClass::class);
```

The DI injection container handles constructor injection and setter injection (interface injection will come, too).
It will use/construct instances for ```Dependency1``` and ```Dependency2``` (asuming they follow the naming conventions, no config is needed for them!),
create a new instance of ```MyClass``` and then injects an instance of ```Dependency3``` using the setter method.
It will not use the setter for ```Dependency2``` as it already was injected by the constructor.

But what if we want an instance of ```ComplexDependency1``` that extends ```Dependency1``` injected upon the construction?
We just pass it as a parameter:
```php
$dep1 = new ComplexDependency1();
$instance = $di->create(MyClass::class, $dep1);
```

The DI container will match the supplied parameters against the required constructor params and uses them instead of resolving the dependency itself.

Configuration registry
----------------------

The DI container can also hold config variables:

```php
// Set variables, the container has a fluid interface!
$di->config('MySQL_Host', 'localhost')
   ->config('MySQL_User', 'root')
   ->config('MySQL_Pass', 'top_secret');

// Will print: localhost
echo $di->config('MySQL_Host');
```

You can acces the variables from everywhere, even in you factory callables:
```php
$di->factory(Database\ConnectionInterface::class, function($di) {
  return new Database\MysqlConnection(
    $di->config('MySQL_Host'),
    $di->config('MySQL_User'),
    $di->config('MySQL_Pass')
  );
});

// Database connection with the config supplied before
$db_handle = $di->get(Database\ConnectionInterface::class);
```
