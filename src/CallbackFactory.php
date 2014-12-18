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
 * Wrapper class for factory callbacks provides by the user.
 *
 * @author Dennis Birkholz <dennis@birkholz.biz>
 */
class CallbackFactory implements FactoryInterface {
	/**
	 * @var string
	 */
	private $responsibleFor;
	
	/**
	 * @var callable
	 */
	private $callback;
	
	
	public function __construct($dependencyName, callable $factoryCallback) {
		$this->responsibleFor = $dependencyName;
		$this->callback = $factoryCallback;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function create(DependencyContainer $di, array &$injectionStatus, array $constructorArgs = array()) {
		\array_unshift($constructorArgs, $di);
		return \call_user_func_array($this->callback, $constructorArgs);
	}

	/**
	 * {@inheritdoc}
	 */
	public function responsibleFor() {
		return $this->responsibleFor;
	}
}
