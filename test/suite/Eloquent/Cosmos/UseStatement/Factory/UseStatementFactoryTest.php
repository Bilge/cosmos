<?php

/*
 * This file is part of the Cosmos package.
 *
 * Copyright © 2013 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eloquent\Cosmos\UseStatement\Factory;

use Eloquent\Cosmos\ClassName\Factory\ClassNameFactory;
use Eloquent\Cosmos\UseStatement\UseStatement;
use Eloquent\Liberator\Liberator;
use PHPUnit_Framework_TestCase;

class UseStatementTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->factory = new UseStatementFactory;

        $this->classNameFactory = new ClassNameFactory;
        $this->className = $this->classNameFactory->create('\Vendor\Package\Class');
        $this->alias = $this->classNameFactory->create('Alias');
    }

    public function testCreate()
    {
        $actual = $this->factory->create($this->className, $this->alias);
        $expected = new UseStatement($this->className, $this->alias);

        $this->assertEquals($expected, $actual);
    }

    public function testInstance()
    {
        $class = Liberator::liberateClass(__NAMESPACE__ . '\UseStatementFactory');
        $class->instance = null;
        $actual = UseStatementFactory::instance();

        $this->assertInstanceOf(__NAMESPACE__ . '\UseStatementFactory', $actual);
        $this->assertSame($actual, UseStatementFactory::instance());
    }
}
