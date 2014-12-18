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
		
		// Supplied arguments for the constructor
		if (\func_num_args() > 1) {
			$suppliedArgs = \func_get_args();
			\array_shift($suppliedArgs);
		} else {
			$suppliedArgs = [];
		}
		
		// Real argument list used for the constructor
		$realArgs = [];
		
		// Use reflection to find constructor parameters and then invoke the constructor
		$reflectionClass = new \ReflectionClass($className);
		
		// No constructor exists, so just instantiate the class
		if (null === ($constructor = $reflectionClass->getConstructor())) {
			$this->logger->debug('No constructor found, creating new instance.');
			return $reflectionClass->newInstanceArgs($suppliedArgs);
		}
		$parameters = $constructor->getParameters();
		
		/* @var $parameter \ReflectionParameter */
		foreach ($parameters as $parameter) {
			// Check if the parameter is typehinted. If yes, check if the next supplied parameter fulfills the hint, otherwise load the dependency
			if (null !== ($requiredClass = $parameter->getClass())) {
				if (isset($suppliedArgs[0]) && is_object($suppliedArgs[0]) && $requiredClass->isInstance($suppliedArgs[0])) {
					$this->logger->debug('Using a supplied argument for parameter #' . ($parameter->getPosition()+1) . ' $' . $parameter->getName() . '');
					$realArgs[] = \array_shift($suppliedArgs);
				}
				
				else {
					$this->logger->debug('Fetching dependency "' . $requiredClass->getName() . '" for parameter #' . ($parameter->getPosition()+1) . ' $' . $parameter->getName() . '');
					if (false !== ($dependencyInstance = $this->tryCreate($requiredClass->getName()))) {
						$realArgs[] = $dependencyInstance;
					}
					
					elseif ($parameter->isOptional()) {
						$this->logger->debug('Using NULL for optional parameter #' . ($parameter->getPosition()+1) . ' $' . $parameter->getName() . '');
						$realArgs[] = null;
					}
					
					else {
						throw new \RuntimeException('Can not resolve dependency for parameter #' . ($parameter->getPosition()+1) . ' $' . $parameter->getName() . '');
					}
				}
			}
			
			// Just pass the parameter
			else {
				if (isset($suppliedArgs[0])) {
					$this->logger->debug('Using a supplied argument for parameter #' . ($parameter->getPosition()+1) . ' $' . $parameter->getName() . '');
					$realArgs[] = \array_shift($suppliedArgs);
				}
				
				elseif ($parameter->isOptional()) {
					$this->logger->debug('Using NULL for optional parameter #' . ($parameter->getPosition()+1) . ' $' . $parameter->getName() . '');
					$realArgs[] = null;
				}
				
				else {
					throw new \RuntimeException('No value supplied for parameter #' . ($parameter->getPosition()+1) . ' $' . $parameter->getName() . '');
				}
			}
		}
		
		$this->logger->debug('Creating new instance of class "' . $reflectionClass->getName() . '" using ' . \count($realArgs) . ' parameters.');
		$instance = $reflectionClass->newInstanceArgs($realArgs);
		return $instance;
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
