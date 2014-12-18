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

use \birkholz\di\tests\ConstructorInjectionClass;
use \birkholz\di\tests\ConstructorInjectionClassWithAdditionalParams;
use \birkholz\di\tests\ConstructorInjectionClassWithOptionalParameters;

use \birkholz\di\dummy\Dummy1Interface;
use \birkholz\di\dummy\Dummy2Interface;
use \birkholz\di\dummy\Dummy3Interface;
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
		$di = new DependencyContainer(new \Monolog\Logger('default', [new \Monolog\Handler\ErrorLogHandler]));
		
		$instance = $di->create(ConstructorInjectionClass::class);
		$this->assertInstanceOf(ConstructorInjectionClass::class, $instance);
		$this->assertInstanceOf(Dummy1Interface::class, $instance->dummy1);
		$this->assertInstanceOf(Dummy2Interface::class, $instance->dummy2);
		$this->assertInstanceOf(Dummy3Interface::class, $instance->dummy3);
	}
	
	/**
	 * Test construction of class with three typehinted constructor parameters.
	 * Supply some of the dependencies as parameters to the create call
	 * 
	 * @test
	 */
	public function testConstructorInjectionWithSuppliedDependencies() {
		$di = new DependencyContainer(new \Monolog\Logger('default', [new \Monolog\Handler\ErrorLogHandler]));
		
		$dummy1 = new DefaultDummy1();
		$dummy3 = new NullDummy3();
		
		$instance = $di->create(ConstructorInjectionClass::class, $dummy1, $dummy3);
		$this->assertInstanceOf(ConstructorInjectionClass::class, $instance);
		$this->assertInstanceOf(Dummy1Interface::class, $instance->dummy1);
		$this->assertInstanceOf(Dummy2Interface::class, $instance->dummy2);
		$this->assertInstanceOf(Dummy3Interface::class, $instance->dummy3);
		$this->assertSame($dummy1, $instance->dummy1);
		$this->assertSame($dummy3, $instance->dummy3);
	}
	
	/**
	 * Supply additional parameters that are passed to the constructor with one provided dependency in between.
	 * 
	 * @test
	 */
	public function testConstructorInjectionWithParameters() {
		$di = new DependencyContainer(new \Monolog\Logger('default', [new \Monolog\Handler\ErrorLogHandler]));
		
		$dummy2 = new DefaultDummy2Impl();
		$config1 = new DefaultDummy4();
		$config2 = "Foo";
		
		$instance = $di->create(ConstructorInjectionClassWithAdditionalParams::class, $config1, $dummy2, $config2);
		$this->assertInstanceOf(ConstructorInjectionClassWithAdditionalParams::class, $instance);
		$this->assertInstanceOf(Dummy1Interface::class, $instance->dummy1);
		$this->assertInstanceOf(Dummy2Interface::class, $instance->dummy2);
		$this->assertInstanceOf(Dummy3Interface::class, $instance->dummy3);
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
		$di = new DependencyContainer(new \Monolog\Logger('default', [new \Monolog\Handler\ErrorLogHandler]));
		
		$instance = $di->create(ConstructorInjectionClassWithAdditionalParams::class);
	}
	
	/**
	 * Skip optional parameters.
	 * 
	 * @test
	 */
	public function testConstructorInjectionWithOptionalParameters() {
		$di = new DependencyContainer(new \Monolog\Logger('default', [new \Monolog\Handler\ErrorLogHandler]));
		
		$instance = $di->create(ConstructorInjectionClassWithOptionalParameters::class);
		$this->assertInstanceOf(ConstructorInjectionClassWithOptionalParameters::class, $instance);
		$this->assertInstanceOf(Dummy1Interface::class, $instance->dummy1);
		$this->assertNull($instance->dummy4);
		$this->assertNull($instance->config1);
	}
}
