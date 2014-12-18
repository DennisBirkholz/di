<?php

/*
 * This file is part of the birkholz/di package.
 * 
 * Copyright (c) Dennis Birkholz <dennis@birkholz.biz>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace birkholz\di;

use \Psr\Log\LoggerInterface;
use \Psr\Log\NullLogger;

/**
 * @author Dennis Birkholz <dennis@birkholz.biz>
 */
class DependencyContainer {
	/**
	 * @var LoggerInterface
	 */
	private $logger;
	
	/**
	 * @var DefaultImplementationFinder
	 */
	private $implementationFinder;
	
	/**
	 * Factory callable supplied by the user.
	 * DependencyName=>factory callback mapping
	 * 
	 * @var array<callable>
	 */
	private $factories = array();
	
	/**
	 * Factory callback for a dependency.
	 * DependencyName=>constructor callback mapping.
	 * 
	 * @var array<FactoryInterface>
	 */
	private $constructors = array();
	
	/**
	 * List of injectors per dependency.
	 * 
	 * @var array<array<InjectorInterface>>
	 */
	private $injectors = array();
	
	/**
	 * Flag to mark dependencies as singletons.
	 * DependencyName => isSingleton mapping.
	 * 
	 * @var array<boolean>
	 */
	private $singleton = array();
	
	/**
	 * Provides a mapping of a dependency is locked and can not be changed.
	 * DependencyName => isLocked mapping.
	 * 
	 * @var array<boolean>
	 */
	private $locked = array();
	
	/**
	 * Provides a mapping from dependency names to the names of classes that can be instantiated.
	 * DependencyName => ClassName mapping.
	 * 
	 * @var array<string>
	 */
	private $implementations = array();
	
	/**
	 * List of created objects.
	 * DependencyName => object instance mapping
	 * 
	 * @var array<object>
	 */
	private $instances = array();
	
	/**
	 * List of configuration parameters stored in the container.
	 * 
	 * @var array<string>
	 */
	private $config = array();
	
	
	
	public function __construct(LoggerInterface $logger = null) {
		$this->logger = ($logger ?: new NullLogger);
		$this->implementationFinder = new DefaultImplementationFinder();
	}
	
	/**
	 * Factory method to create a new object of the supplied class or interface $dependencyName.
	 * Additional parameters are supplied as parameters to the constructor of the dependency.
	 * 
	 * Type hinted constructor parameters are used from the optional parameters if the type matches.
	 * Otherwise the container tries to fulfill that dependency.
	 * 
	 * If the dependency can not be fulfilled, an exception is thrown.
	 * 
	 * @param string $dependencyName The fully qualified interface/class name of the object to create.
	 * @param mixed $constructorArg1
	 * @param mixed $constructorArg2
	 * @return object New object of the supplied class or interface $dependencyName
	 * @api
	 */
	public function create($dependencyName, $constructorArg1 = null, $constructorArg2 = null) {
		$constructor = $this->resolveConstructor($dependencyName);
		
		// Supplied arguments for the constructor
		if (\func_num_args() > 1) {
			$constructorArgs = \func_get_args();
			\array_shift($constructorArgs);
		} else {
			$constructorArgs = array();
		}
		
		$injected = array();
		$object = \call_user_func_array($constructor, array($this, &$injected, $constructorArgs));
		
		if (!isset($this->injectors[$dependencyName])) {
			$this->injectors[$dependencyName] = $this->injectorFactory($dependencyName);
		}
		
		foreach ($this->injectors[$dependencyName] as $injector) {
			$injector($this, $object, $injected);
		}
		
		return $object;
	}
	
	/**
	 * Get a callable method for the supplied dependency name
	 * 
	 * @param string $dependencyName
	 * @return callable
	 */
	private function resolveConstructor($dependencyName) {
		// Constructor is set for the dependency
		if (isset($this->constructors[$dependencyName])) {
			return $this->constructors[$dependencyName];
		}
		
		if (isset($this->factories[$dependencyName])) {
			$this->constructors[$dependencyName] = $this->factoryFactory($dependencyName, $this->factories[$dependencyName]);
			return $this->constructors[$dependencyName];
		}
		
		// The dependency can be satisfied by another dependency, so try that constructor
		if (isset($this->implementations[$dependencyName])) {
			return $this->resolveConstructor($this->implementations[$dependencyName]);
		}
		
		// Dependency is a class, so create a factory and use that
		if (\class_exists($dependencyName)) {
			$this->constructors[$dependencyName] = $this->factoryFactory($dependencyName);
			return $this->constructors[$dependencyName];
		}
		
		// Check if the supplied dependency name is an interface
		if (\interface_exists($dependencyName)) {
			if (false === ($className = $this->implementationFinder->findImplementation($dependencyName))) {
				throw new \RuntimeException('Required dependency "' . $dependencyName . '" has no default class.');
			}
			
			return $this->resolveConstructor($className);
		}
		
		throw new \RuntimeException('Required dependency "' . $dependencyName . '" has no class or interface to satisfy it.');
	}
	
	
	/**
	 * Factory method to create a new object of the supplied class or interface $dependencyName.
	 * Additional parameters are supplied as parameters to the constructor of the dependency.
	 * 
	 * Type hinted constructor parameters are used from the optional parameters if the type matches.
	 * Otherwise the container tries to fulfill that dependency.
	 * 
	 * If the dependency can not be fulfilled, false is returned.
	 * 
	 * @param string $dependencyName The fully qualified interface/class name of the object to create.
	 * @param mixed $constructorArg1
	 * @param mixed $constructorArg2
	 * @return object|false New object of the supplied class or interface $dependencyName
	 * @api
	 */
	public function tryCreate($dependencyName, $constructorArg1 = null, $constructorArg2 = null) {
		try {
			return \call_user_func_array(array($this, 'create'), \func_get_args());
		} catch (\Exception $ex) {
			return false;
		}
	}
	
	/**
	 * Get an object instance that fulfills the dependency.
	 * 
	 * @param string $dependencyName
	 * @return object An instance for the dependency
	 * @api
	 */
	public function get($dependencyName) {
		if (!isset($this->instances[$dependencyName])) {
			$this->instances[$dependencyName] = $this->create($dependencyName);
		}
		
		return $this->instances[$dependencyName];
	}
	
	/**
	 * 
	 * @param string $dependencyName
	 * @return object|false An instance for the dependency
	 * @api
	 */
	public function tryGet($dependencyName) {
		try {
			return $this->get($dependencyName);
		} catch (\Exception $ex) {
			return false;
		}
	}
	
	/**
	 * Mark a specific dependency to be a singleton.
	 * Only one instance of the singleton can be constructed. Successive create() calls will fail.
	 * Be sure to always receive the singleton with the get() method.
	 * 
	 * The second parameter can be either false to clear a previously set $singleton flag
	 * or a callable that is to be used as the factory if the singleton is not yet instantiated.
	 * 
	 * @param string $dependencyName
	 * @param callable|false $factoryMethodOrDisable
	 * @return DependencyContainer $this for chaining
	 * @api
	 */
	public function singleton($dependencyName, $factoryMethodOrDisable = null) {
		if ($factoryMethodOrDisable === false) {
			unset($this->singleton[$dependencyName]);
			return $this;
		}
		
		$this->singleton[$dependencyName] = true;
		if (is_callable($factoryMethodOrDisable)) {
			$this->factory($dependencyName, $factoryMethodOrDisable);
		}
		return $this;
	}
	
	/**
	 * Register a factory method for the supplied dependency name
	 * 
	 * @param string $dependencyName
	 * @param callable $factory
	 * @return DependencyContainer $this for chaining
	 * @api
	 */
	public function factory($dependencyName, callable $factory) {
		if (isset($this->singleton[$dependencyName]) && isset($this->instances[$dependencyName])) {
			throw new \RuntimeException('Can not change factory method for already instantiated singleton "' . $dependencyName . '".');
		}
		
		$this->factories[$dependencyName] = $factory;
		unset($this->constructors[$dependencyName]);
		
		return $this;
	}
	
	/**
	 * Get or set a config variable.
	 * If only the $name parameter is supplied, the value of that variable is returned.
	 * An exception is thrown if the parameter has not been set before.
	 * If both parameters are supplied, the $value is stored and $this is returned for chaining.
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return DependencyContainer|mixed
	 */
	public function config($name, $value = null) {
		if ($value === null) {
			if (!isset($this->config[$name])) {
				throw new \RuntimeException('Unknown config variable "' . $name . '".');
			}
			return $this->config[$name];
		}
		
		else {
			$this->config[$name] = $value;
			return $this;
		}
	}
	
	/**
	 * Set the implementation a dependency uses.
	 * 
	 * @param string $dependencyName
	 * @param string $className
	 * @return \birkholz\di\DependencyContainer $this for chaining
	 * @api
	 */
	public function uses($dependencyName, $className) {
		$this->implementations[$dependencyName] = $className;
		return $this;
	}
	
	/**
	 * Create a new factory for the supplied dependency name.
	 * A factory may be used that wraps the supplied callback.
	 * Otherwise a default constructor injection factory is used.
	 * 
	 * @param string $dependencyName
	 * @param callable $callback (optional)
	 * @return \birkholz\di\FactoryInterface
	 */
	private function factoryFactory($dependencyName, callable $callback = null) {
		if (null !== $callback) {
			return new CallbackFactory($dependencyName, $this->factories[$dependencyName]);
		}
		
		else {
			return new ConstructorInjectionFactory($dependencyName);
		}
	}
	
	/**
	 * This factory method constructs a SetterInjector and an InterfaceInjector for each dependency.
	 * 
	 * @param string $dependencyName
	 * @return array<InjectorInterface>
	 */
	private function injectorFactory($dependencyName) {
		$injectors = array();
		$injectors[] = new SetterInjector($dependencyName);
		return $injectors;
	}
}
