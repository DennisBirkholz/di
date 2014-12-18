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

use \birkholz\di\dummy\DefaultDummy1;
use \birkholz\di\dummy\DefaultDummy2Impl;
use \birkholz\di\dummy\NullDummy3;
use \birkholz\di\dummy\DefaultDummy4;

/**
 * @author Dennis Birkholz <dennis@birkholz.biz>
 */
class DependencyContainerTest extends \PHPUnit_Framework_TestCase {
	/**
	 * Test construction of class with three typehinted constructor parameters
	 * 
	 * @test
	 */
	public function testConstructorInjection() {
		$di = new DependencyContainer();
		
		$instance = $di->create('birkholz\\di\\tests\\ConstructorInjectionClass');
		$this->assertInstanceOf('birkholz\\di\\tests\\ConstructorInjectionClass', $instance);
		$this->assertInstanceOf('birkholz\\di\\dummy\\Dummy1Interface', $instance->dummy1);
		$this->assertInstanceOf('birkholz\\di\\dummy\\Dummy2Interface', $instance->dummy2);
		$this->assertInstanceOf('birkholz\\di\\dummy\\Dummy3Interface', $instance->dummy3);
	}
	
	/**
	 * Test construction of class with three typehinted constructor parameters.
	 * Supply some of the dependencies as parameters to the create call
	 * 
	 * @test
	 */
	public function testConstructorInjectionWithSuppliedDependencies() {
		$di = new DependencyContainer();
		
		$dummy1 = new DefaultDummy1();
		$dummy3 = new NullDummy3();
		
		$instance = $di->create('birkholz\\di\\tests\\ConstructorInjectionClass', $dummy1, $dummy3);
		$this->assertInstanceOf('birkholz\\di\\tests\\ConstructorInjectionClass', $instance);
		$this->assertInstanceOf('birkholz\\di\\dummy\\Dummy1Interface', $instance->dummy1);
		$this->assertInstanceOf('birkholz\\di\\dummy\\Dummy2Interface', $instance->dummy2);
		$this->assertInstanceOf('birkholz\\di\\dummy\\Dummy3Interface', $instance->dummy3);
		$this->assertSame($dummy1, $instance->dummy1);
		$this->assertSame($dummy3, $instance->dummy3);
	}
	
	/**
	 * Supply additional parameters that are passed to the constructor with one provided dependency in between.
	 * 
	 * @test
	 */
	public function testConstructorInjectionWithParameters() {
		$di = new DependencyContainer();
		
		$dummy2 = new DefaultDummy2Impl();
		$config1 = new DefaultDummy4();
		$config2 = "Foo";
		
		$instance = $di->create('birkholz\\di\\tests\\ConstructorInjectionClassWithAdditionalParams', $config1, $dummy2, $config2);
		$this->assertInstanceOf('birkholz\\di\\tests\\ConstructorInjectionClassWithAdditionalParams', $instance);
		$this->assertInstanceOf('birkholz\\di\\dummy\\Dummy1Interface', $instance->dummy1);
		$this->assertInstanceOf('birkholz\\di\\dummy\\Dummy2Interface', $instance->dummy2);
		$this->assertInstanceOf('birkholz\\di\\dummy\\Dummy3Interface', $instance->dummy3);
		$this->assertSame($dummy2, $instance->dummy2);
		$this->assertSame($config1, $instance->config1);
		$this->assertSame($config2, $instance->config2);
	}
	
	/**
	 * Do not provide a required parameter.
	 * 
	 * @test
	 * @expectedException \Exception
	 */
	public function testConstructorInjectionWithFewParameters() {
		$di = new DependencyContainer();
		
		$instance = $di->create('birkholz\\di\\tests\\ConstructorInjectionClassWithAdditionalParams');
	}
	
	/**
	 * Skip optional parameters.
	 * 
	 * @test
	 */
	public function testConstructorInjectionWithOptionalParameters() {
		$di = new DependencyContainer();
		
		$instance = $di->create('birkholz\\di\\tests\\ConstructorInjectionClassWithOptionalParameters');
		$this->assertInstanceOf('birkholz\\di\\tests\\ConstructorInjectionClassWithOptionalParameters', $instance);
		$this->assertInstanceOf('birkholz\\di\\dummy\\Dummy1Interface', $instance->dummy1);
		$this->assertNull($instance->dummy4);
		$this->assertNull($instance->config1);
	}
}
