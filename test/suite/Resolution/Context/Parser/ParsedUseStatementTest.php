<?php

/*
 * This file is part of the Cosmos package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Cosmos\Resolution\Context\Parser;

use Eloquent\Cosmos\Symbol\Symbol;
use Eloquent\Cosmos\UseStatement\UseStatement;
use Eloquent\Cosmos\UseStatement\UseStatementType;
use PHPUnit_Framework_TestCase;
use Phake;

/**
 * @covers \Eloquent\Cosmos\Resolution\Context\Parser\ParsedUseStatement
 * @covers \Eloquent\Cosmos\Resolution\Context\Parser\AbstractParsedElement
 */
class ParsedUseStatementTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->symbol = Symbol::fromString('\Namespace\Symbol');
        $this->alias = Symbol::fromString('Alias');
        $this->useStatement = new UseStatement($this->symbol, $this->alias, UseStatementType::CONSTANT());
        $this->position = new ParserPosition(111, 222);
        $this->parsedUseStatement = new ParsedUseStatement($this->useStatement, $this->position);
    }

    public function testConstructor()
    {
        $this->assertSame($this->useStatement, $this->parsedUseStatement->useStatement());
        $this->assertSame($this->position, $this->parsedUseStatement->position());
        $this->assertSame($this->useStatement->symbol(), $this->parsedUseStatement->symbol());
        $this->assertSame($this->useStatement->alias(), $this->parsedUseStatement->alias());
        $this->assertSame($this->useStatement->effectiveAlias(), $this->parsedUseStatement->effectiveAlias());
        $this->assertSame($this->useStatement->type(), $this->parsedUseStatement->type());
        $this->assertSame($this->useStatement->string(), $this->parsedUseStatement->string());
        $this->assertSame(strval($this->useStatement), strval($this->parsedUseStatement));
    }

    public function testConstructorDefaults()
    {
        $this->parsedUseStatement = new ParsedUseStatement($this->useStatement);

        $this->assertEquals(new ParserPosition(0, 0), $this->parsedUseStatement->position());
    }

    public function testAccept()
    {
        $visitor = Phake::mock('Eloquent\Cosmos\Resolution\Context\ResolutionContextVisitorInterface');
        $this->parsedUseStatement->accept($visitor);

        Phake::verify($visitor)->visitUseStatement($this->identicalTo($this->parsedUseStatement));
    }
}