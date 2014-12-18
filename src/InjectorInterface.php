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
 * Injectors are used to wire dependencies after an object instance has been created.
 * 
 * @author Dennis Birkholz <dennis@birkholz.biz>
 */
interface InjectorInterface {
	/**
	 * @param string $dependencyName
	 */
	function __construct($dependencyName);
	
	/**
	 * Get the class this injector can handle
	 * 
	 * @return string
	 */
	function responsibleFor();
	
	/**
	 * Method to actually inject the dependencies.
	 * 
	 * $injectStatus contains already injected dependencies to avoid duplicate injections.
	 * Changed by this method.
	 * 
	 * @param \birkholz\di\DependencyContainer $di
	 * @param object $object
	 * @param array &$injectionStatus
	 */
	function __invoke(DependencyContainer $di, $object, array &$injectionStatus);
}
