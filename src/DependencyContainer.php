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
	
	
	public function __construct(LoggerInterface $logger = null) {
		$this->logger = ($logger ?: new NullLogger);
		$this->implementationFinder = new DefaultImplementationFinder();
	}
	
	private $constructors = [];
	
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
	 */
	public function create($dependencyName, $constructorArg1 = null, $constructorArg2 = null) {
		// Check if the supplied dependency name is an interface
		if (\interface_exists($dependencyName)) {
			if (false === ($className = $this->implementationFinder->findImplementation($dependencyName))) {
				throw new \RuntimeException('Required dependency "' . $dependencyName . '" has no default class.');
			}
			
			$this->logger->debug('Required dependency "' . $dependencyName . '" is fulfilled by default class "' . $className . '"');
		}
		
		elseif (\class_exists($dependencyName)) {
			$this->logger->debug('Required dependency "' . $dependencyName . '" is a class, try to create it.');
			$className = $dependencyName;
		}
		
		else {
			throw new \RuntimeException('Required dependency "' . $dependencyName . '" is no class or interface to satisfy.');
		}
		
		if (!isset($this->constructors[$className])) {
			$this->constructors[$className] = [new ConstructorInjectionFactory($className), 'create'];
		}
		
		// Supplied arguments for the constructor
		if (\func_num_args() > 1) {
			$constructorArgs = \func_get_args();
			\array_shift($constructorArgs);
		} else {
			$constructorArgs = [];
		}
		
		$injected = [];
		return $this->constructors[$className]($this, $injected, $constructorArgs);
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
	 * @return boolean|object New object of the supplied class or interface $dependencyName
	 */
	public function tryCreate($dependencyName, $constructorArg1 = null, $constructorArg2 = null) {
		try {
			return \call_user_func_array([$this, 'create'], \func_get_args());
		}
		
		catch (\Exception $ex) {
			return false;
		}
	}
}
