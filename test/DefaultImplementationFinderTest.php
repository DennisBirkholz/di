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
 * @author Dennis Birkholz <dennis@birkholz.biz>
 * @covers \birkholz\di\DefaultImplementationFinder
 */
class DefaultImplementationFinderTest extends \PHPUnit_Framework_TestCase {
	public function testInterfaceViolatesConvention() {
		$finder = new DefaultImplementationFinder;
		$this->assertFalse($finder->findImplementation('namespace\\bla\\WrongInterfaceClassName'));
	}
	
	public function testInterfaceDoesNotExist() {
		$finder = new DefaultImplementationFinder;
		$this->assertFalse($finder->findImplementation('birkholz\\di\\NonExistingInterface'));
	}
	
	public function testSimpleNameVariations() {
		$finder = new DefaultImplementationFinder();
		
		$ns = 'birkholz\\di\\dummy\\';
		
		$combinations = [
			[ 'Dummy1Interface', 'DefaultDummy1', ],
			[ 'Dummy2Interface', 'DefaultDummy2Impl', ],
			[ 'Dummy3Interface', 'NullDummy3', ],
		];
		
		foreach ($combinations as list($interface, $class)) {
			$this->assertEquals($ns.$class, $finder->findImplementation($ns.$interface));
		}
	}
	
	public function testClassesImplementInterface() {
		$finder = new DefaultImplementationFinder;
		$this->assertFalse($finder->findImplementation('birkholz\\di\\dummy\\Dummy4Interface'));
	}
	
	public function testInterfaceNormalization() {
		$finder = new DefaultImplementationFinder;
		$this->assertEquals(
			$finder->findImplementation('birkholz\\di\\dummy\\Dummy1Interface'),
			$finder->findImplementation('\\birkholz\\di\\dummy\\Dummy1Interface')
		);
	}
}
