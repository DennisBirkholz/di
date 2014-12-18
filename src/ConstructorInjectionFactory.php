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

/**
 * The constructor injection constructor class 
 * 
 * @author Dennis Birkholz <dennis@birkholz.biz>
 */
class ConstructorInjectionFactory implements FactoryInterface {
	/**
	 * The name of the class this factory is responsible for
	 * @var string
	 */
	private $responsibleFor;
	
	/**
	 * Information about the constructor parameters.
	 * @var array
	 */
	private $parameters;
	
	
	
	
	/**
	 * Create a new factory for the supplied class name
	 * 
	 * @param string $responsibleFor
	 */
	public function __construct($responsibleFor) {
		$this->responsibleFor = $responsibleFor;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function responsibleFor() {
		return $this->responsibleFor;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function create(DependencyContainer $di, array &$injectionStatus, array $constructorArgs = array()) {
		$this->initialize();
		
		// Real argument list used for the constructor
		$realArgs = array();
		
		// Resolve all parameters
		foreach ($this->parameters as $parameterPos => $parameter) {
			// Check if the parameter is typehinted. If yes, check if the next supplied parameter fulfills the hint, otherwise load the dependency
			if (isset($parameter['class'])) {
				// Next supplied parameter fulfills dependency
				if (isset($constructorArgs[0]) && \is_a($constructorArgs[0], $parameter['class'])) {
					$realArgs[] = \array_shift($constructorArgs);
					$injectionStatus[] = $parameter['class'];
				}
				
				// Use container to get dependency
				elseif (false !== ($dependencyInstance = $di->tryCreate($parameter['class']))) {
					$realArgs[] = $dependencyInstance;
					$injectionStatus[] = $parameter['class'];
				}

				elseif ($parameter['optional']) {
					$realArgs[] = null;
				}

				else {
					throw new \RuntimeException('Can not resolve dependency for parameter #' . ($parameterPos+1) . ' $' . $parameter['name'] . '');
				}
			}
			
			// Just pass a parameter from outside
			elseif (\count($constructorArgs)) {
				$realArgs[] = \array_shift($constructorArgs);
			}
			
			// Set optional parameter null
			elseif ($parameter['optional']) {
				$realArgs[] = null;
			}
			
			else {
				throw new \RuntimeException('No value supplied for parameter #' . ($parameterPos+1) . ' $' . $parameter['name'] . '');
			}
		}
		
		// Use reflection to find constructor parameters and then invoke the constructor
		$reflectionClass = new \ReflectionClass($this->responsibleFor);
		return $reflectionClass->newInstanceArgs($realArgs);
	}
	
	/**
	 * Initialize the $parameters array.
	 */
	protected function initialize() {
		// Only initialize on first call
		if (\is_array($this->parameters)) {
			return;
		}
		
		// Use reflection to find constructor parameters
		$reflectionClass = new \ReflectionClass($this->responsibleFor);
		
		// No constructor exists, so just instantiate the class
		if (null === ($constructor = $reflectionClass->getConstructor())) {
			$this->parameters = array();
			return;
		}
		
		/* @var $parameter \ReflectionParameter */
		foreach ($constructor->getParameters() as $parameter) {
			$setting = array(
				'name' => $parameter->getName(),
				'optional' => $parameter->isOptional(),
			);
			
			if (null !== ($requiredClass = $parameter->getClass())) {
				$setting['class'] = $requiredClass->getName();
			}
			
			$this->parameters[] = $setting;
		}
	}
}
