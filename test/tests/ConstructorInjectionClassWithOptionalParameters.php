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
use \birkholz\di\dummy\Dummy4Interface;

/**
 * @author Dennis Birkholz <dennis@birkholz.biz>
 */
class ConstructorInjectionClassWithOptionalParameters {
	public $dummy1;
	public $dummy4;
	public $config1;
	
	public function __construct(Dummy1Interface $dummy1, Dummy4Interface $dummy4 = null, $config1 = null) {
		$this->dummy1 = $dummy1;
		$this->dummy4 = $dummy4;
		$this->config1 = $config1;
	}
}
