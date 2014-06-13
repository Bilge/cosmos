<?php

/*
 * This file is part of the Cosmos package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Cosmos\Resolution\Context\Renderer;

use Eloquent\Cosmos\Resolution\Context\ResolutionContext;
use Eloquent\Cosmos\Symbol\Symbol;
use Eloquent\Cosmos\UseStatement\UseStatement;
use Eloquent\Cosmos\UseStatement\UseStatementType;
use Eloquent\Liberator\Liberator;
use PHPUnit_Framework_TestCase;

class ResolutionContextRendererTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->renderer = new ResolutionContextRenderer;
    }

    public function testRenderContext()
    {
        $context = new ResolutionContext(
            Symbol::fromString('\NamespaceA\NamespaceB'),
            array(
                new UseStatement(Symbol::fromString('\NamespaceC\NamespaceD\SymbolA')),
                new UseStatement(Symbol::fromString('\NamespaceE\NamespaceF\SymbolB'), Symbol::fromString('SymbolC')),
                new UseStatement(Symbol::fromString('\SymbolD')),
                new UseStatement(Symbol::fromString('\SymbolE'), Symbol::fromString('SymbolF')),
                new UseStatement(Symbol::fromString('\SymbolG'), null, UseStatementType::FUNCT1ON()),
                new UseStatement(
                    Symbol::fromString('\SymbolH'),
                    Symbol::fromString('SymbolI'),
                    UseStatementType::FUNCT1ON()
                ),
                new UseStatement(Symbol::fromString('\SymbolJ'), null, UseStatementType::CONSTANT()),
                new UseStatement(
                    Symbol::fromString('\SymbolK'),
                    Symbol::fromString('SymbolL'),
                    UseStatementType::CONSTANT()
                ),
            )
        );
        $expected = <<<'EOD'
namespace NamespaceA\NamespaceB;

use NamespaceC\NamespaceD\SymbolA;
use NamespaceE\NamespaceF\SymbolB as SymbolC;
use SymbolD;
use SymbolE as SymbolF;
use function SymbolG;
use function SymbolH as SymbolI;
use const SymbolJ;
use const SymbolK as SymbolL;

EOD;

        $this->assertSame($expected, $this->renderer->renderContext($context));
    }

    public function testRenderContextWithGlobalNamespace()
    {
        $context = new ResolutionContext(
            null,
            array(
                new UseStatement(Symbol::fromString('\NamespaceC\NamespaceD\SymbolA')),
                new UseStatement(Symbol::fromString('\NamespaceE\NamespaceF\SymbolB'), Symbol::fromString('SymbolC')),
                new UseStatement(Symbol::fromString('\SymbolD')),
                new UseStatement(Symbol::fromString('\SymbolE'), Symbol::fromString('SymbolF')),
                new UseStatement(Symbol::fromString('\SymbolG'), null, UseStatementType::FUNCT1ON()),
                new UseStatement(
                    Symbol::fromString('\SymbolH'),
                    Symbol::fromString('SymbolI'),
                    UseStatementType::FUNCT1ON()
                ),
                new UseStatement(Symbol::fromString('\SymbolJ'), null, UseStatementType::CONSTANT()),
                new UseStatement(
                    Symbol::fromString('\SymbolK'),
                    Symbol::fromString('SymbolL'),
                    UseStatementType::CONSTANT()
                ),
            )
        );
        $expected = <<<'EOD'
use NamespaceC\NamespaceD\SymbolA;
use NamespaceE\NamespaceF\SymbolB as SymbolC;
use SymbolD;
use SymbolE as SymbolF;
use function SymbolG;
use function SymbolH as SymbolI;
use const SymbolJ;
use const SymbolK as SymbolL;

EOD;

        $this->assertSame($expected, $this->renderer->renderContext($context));
    }

    public function testInstance()
    {
        $class = get_class($this->renderer);
        $liberatedClass = Liberator::liberateClass($class);
        $liberatedClass->instance = null;
        $actual = $class::instance();

        $this->assertInstanceOf($class, $actual);
        $this->assertSame($actual, $class::instance());
    }
}
