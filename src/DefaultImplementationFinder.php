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

use \Psr\Log\LoggerInterface;
use \Psr\Log\NullLogger;

/**
 * Class to find the default implementation for an interface.
 * 
 * By convention, the interface should be named like:
 * namespace\\subnamespace\\XyzAbcInterface
 * 
 * The following class names are tested:
 * 
 * namespace\\subnamespace\\DefaultXyzAbc
 * namespace\\subnamespace\\DefaultXyzAbcImpl
 * namespace\\subnamespace\\NullXyzAbc (for Psr\Log\LoggerInterface)
 * 
 * @author Dennis Birkholz <dennis@birkholz.biz>
 */
class DefaultImplementationFinder {
	/**
	 * @var LoggerInterface
	 */
	private $logger;
	
	public function __construct(LoggerInterface $logger = null) {
		$this->logger = ($logger ?: new NullLogger);
	}
	
	public function findImplementation($fullQualifiedInterfaceName) {
		if ('Interface' !== \substr($fullQualifiedInterfaceName, -9)) {
			$this->logger->debug('Interface name violates convention, "Interface" postfix missing.');
			return false;
		}
		
		if ('\\' === $fullQualifiedInterfaceName[0]) {
			$this->logger->debug('Removing leading backslash from interface name for normalization.');
			$fullQualifiedInterfaceName = \substr($fullQualifiedInterfaceName, 1);
		}
		
		if (false !== ($pos = \strrpos($fullQualifiedInterfaceName, '\\'))) {
			$namespace = \substr($fullQualifiedInterfaceName, 0, $pos+1);
			$interface = \substr($fullQualifiedInterfaceName, $pos+1);
			$this->logger->debug('Interface is "' . $interface . '" in namespace "' . $namespace . '"');
		}
		
		else {
			$namespace = '';
			$interface = $fullQualifiedInterfaceName;
			$this->logger->debug('Interface is "' . $interface . '" without namespace');
		}
		
		// Remove "Interface" postfix
		$basename = \substr($interface, 0, \strlen($interface)-9);
		$this->logger->debug('Using basename "' . $basename . '"');
		
		// Pre and postfix pairs to try to build classnames from
		$preAndPostfixes = array(
			array( 'Default', '', ),
			array( 'Default', 'Impl', ),
			array( 'Null', '', ),
		);
		
		// Check if constructed class names exist and implement the interface
		foreach ($preAndPostfixes as $preAndPostfix) {
			list($prefix, $postfix) = $preAndPostfix;
			$fqcn = $namespace . $prefix . $basename . $postfix;
			$this->logger->debug('Checking class "' . $fqcn . '"');
			
			if (!\class_exists($fqcn)) {
				$this->logger->debug('Class does not exist.');
				continue;
			}
			
			if (!\is_a($fqcn, $fullQualifiedInterfaceName, true)) {
				$this->logger->debug('Class does not implement the interface.');
				continue;
			}
			
			$this->logger->debug('Found class.');
			return $fqcn;
		}
		
		$this->logger->debug('No class found.');
		return false;
	}
}
