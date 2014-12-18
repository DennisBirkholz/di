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
 * Objects implementing the factory interface can be used to create objects of the type they are responsible for.
 * 
 * @author Dennis Birkholz <dennis@birkholz.biz>
 */
interface FactoryInterface {
	/**
	 * Get the class this factory can create.
	 * 
	 * @return string
	 */
	function responsibleFor();
	
	/**
	 * Factory method to create a new object of the type this factory is responsible for.
	 * 
	 * If the factory also injects dependencies when creating the object,
	 *  it should store these dependencies in the $injectionStatus array to avoid doublicate injection.
	 * 
	 * @param \birkholz\di\DependencyContainer $di
	 * @param array &$injectionStatus
	 * @param array $constructorArgs
	 */
	public function create(DependencyContainer $di, array &$injectionStatus, array $constructorArgs = []);
}
