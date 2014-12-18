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
 * Description of SetterInjector
 *
 * @author Dennis Birkholz <dennis@birkholz.biz>
 */
class SetterInjector implements InjectorInterface {
	/**
	 * @var string
	 */
	private $responsibleFor;
	
	/**
	 * List of setter methods
	 * 
	 * @var array
	 */
	private $methods;
	
	
	
	/**
	 * {@inheritdoc}
	 */
	public function __construct($dependencyName) {
		$this->responsibleFor = $dependencyName;
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
	public function __invoke(DependencyContainer $di, $object, array &$injectionStatus) {
		$this->initialize();
		
		foreach ($this->methods as $method) {
			// Dependency already injected
			if (isset($injectionStatus[$method['class']])) { continue; }
			
			\call_user_func(array($object, $method['name']), $di->get($method['class']));
			$injectionStatus[$method['class']] = true;
		}
	}
	
	/**
	 * Initialize setter methods
	 */
	private function initialize() {
		// Initialize only once
		if (is_array($this->methods)) { return; }
		
		$this->methods = [];
		
		$reflectionClass = new \ReflectionClass($this->responsibleFor);
		
		/* @var $reflectionMethod \ReflectionMethod */
		foreach ($reflectionClass->getMethods() as $reflectionMethod) {
			// Convention says: setDependency() is the method name
			if ('set' !== \substr($reflectionMethod->getName(), 0, 3)) { continue; }
			
			/* @var $reflectionParameters array<\ReflectionParameter> */
			$reflectionParameters = $reflectionMethod->getParameters();
			
			// Method should only have one parameter: the dependency to be injected
			if (\count($reflectionParameters) !== 1) { continue; }
			
			// Ignore setter without type hint
			if (null === ($paramReflectionClass = $reflectionParameters[0]->getClass())) { continue; }
			
			$this->methods[] = array(
				'name' => $reflectionMethod->getName(),
				'class' => $paramReflectionClass->getName(),
			);
		}
	}
}
