<?php

/*
 * This file is part of the Cosmos package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Cosmos\Resolution;

use Eloquent\Cosmos\ClassName\Factory\ClassNameFactory;
use Eloquent\Cosmos\ClassName\QualifiedClassName;
use Eloquent\Cosmos\UseStatement\UseStatement;
use PHPUnit_Framework_TestCase;

class ResolutionContextTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->factory = new ClassNameFactory;
        $this->primaryNamespace = $this->factory->create('\VendorA\PackageA');
        $this->useStatements = array(
            new UseStatement($this->factory->create('\VendorB\PackageB')),
            new UseStatement($this->factory->create('\VendorC\PackageC')),
        );
        $this->context = new ResolutionContext($this->primaryNamespace, $this->useStatements, $this->factory);
    }

    public function testConstructor()
    {
        $this->assertSame($this->primaryNamespace, $this->context->primaryNamespace());
        $this->assertSame($this->useStatements, $this->context->useStatements());
    }

    public function testConstructorDefaults()
    {
        $this->context = new ResolutionContext;

        $this->assertEquals(new QualifiedClassName(array()), $this->context->primaryNamespace());
        $this->assertSame(array(), $this->context->useStatements());
    }

    public function testClassNameByShortName()
    {
        $this->context = new ResolutionContext(
            $this->factory->create('\foo'),
            array(
                new UseStatement($this->factory->create('\My\Full\Classname'), $this->factory->create('Another')),
                new UseStatement($this->factory->create('\My\Full\NSname')),
                new UseStatement($this->factory->create('\ArrayObject')),
            )
        );

        $this->assertSame(
            '\My\Full\Classname',
            $this->context->classNameByShortName($this->factory->create('Another'))->string()
        );
        $this->assertSame(
            '\My\Full\NSname',
            $this->context->classNameByShortName($this->factory->create('NSname'))->string()
        );
        $this->assertSame(
            '\ArrayObject',
            $this->context->classNameByShortName($this->factory->create('ArrayObject'))->string()
        );
        $this->assertNull($this->context->classNameByShortName($this->factory->create('Classname')));
        $this->assertNull($this->context->classNameByShortName($this->factory->create('FooClass')));
    }
}
