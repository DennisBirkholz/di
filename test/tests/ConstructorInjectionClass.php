<?php

/*
 * This file is part of the birkholz/di package.
 * 
 * Copyright (c) Dennis Birkholz <dennis@birkholz.biz>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace birkholz\di\tests;

use \birkholz\di\dummy\Dummy1Interface;
use \birkholz\di\dummy\Dummy2Interface;
use \birkholz\di\dummy\Dummy3Interface;

/**
 * @author Dennis Birkholz <dennis@birkholz.biz>
 */
class ConstructorInjectionClass {
	public $dummy1;
	public $dummy2;
	public $dummy3;
	
	public function __construct(Dummy1Interface $dummy1, Dummy2Interface $dummy2, Dummy3Interface $dummy3) {
		$this->dummy1 = $dummy1;
		$this->dummy2 = $dummy2;
		$this->dummy3 = $dummy3;
	}
}
