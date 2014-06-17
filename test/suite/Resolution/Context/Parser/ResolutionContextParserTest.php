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

use Eloquent\Cosmos\Resolution\Context\Factory\ResolutionContextFactory;
use Eloquent\Cosmos\Resolution\Context\Renderer\ResolutionContextRenderer;
use Eloquent\Cosmos\Resolution\SymbolResolver;
use Eloquent\Cosmos\Symbol\Factory\SymbolFactory;
use Eloquent\Cosmos\Symbol\Normalizer\SymbolNormalizer;
use Eloquent\Cosmos\UseStatement\Factory\UseStatementFactory;
use Eloquent\Cosmos\UseStatement\UseStatement;
use Eloquent\Liberator\Liberator;
use Icecave\Isolator\Isolator;
use Phake;
use PHPUnit_Framework_TestCase;

class ResolutionContextParserTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->symbolFactory = new SymbolFactory;
        $this->symbolResolver = new SymbolResolver;
        $this->symbolNormalizer = new SymbolNormalizer;
        $this->useStatementFactory = new UseStatementFactory;
        $this->contextFactory = new ResolutionContextFactory;
        $this->tokenNormalizer = new TokenNormalizer;
        $this->isolator = Phake::mock(Isolator::className());
        Phake::when($this->isolator)->defined('T_TRAIT')->thenReturn(false);
        $this->parser = new ResolutionContextParser(
            $this->symbolFactory,
            $this->symbolResolver,
            $this->symbolNormalizer,
            $this->useStatementFactory,
            $this->contextFactory,
            $this->tokenNormalizer,
            $this->isolator
        );

        $this->contextRenderer = ResolutionContextRenderer::instance();
    }

    public function testConstructor()
    {
        $this->assertSame($this->symbolFactory, $this->parser->symbolFactory());
        $this->assertSame($this->symbolResolver, $this->parser->symbolResolver());
        $this->assertSame($this->symbolNormalizer, $this->parser->symbolNormalizer());
        $this->assertSame($this->useStatementFactory, $this->parser->useStatementFactory());
        $this->assertSame($this->contextFactory, $this->parser->contextFactory());
        $this->assertSame($this->tokenNormalizer, $this->parser->tokenNormalizer());
    }

    public function testConstructorDefaults()
    {
        $this->parser = new ResolutionContextParser;

        $this->assertSame(SymbolFactory::instance(), $this->parser->symbolFactory());
        $this->assertSame(SymbolResolver::instance(), $this->parser->symbolResolver());
        $this->assertSame(SymbolNormalizer::instance(), $this->parser->symbolNormalizer());
        $this->assertSame(UseStatementFactory::instance(), $this->parser->useStatementFactory());
        $this->assertSame(ResolutionContextFactory::instance(), $this->parser->contextFactory());
        $this->assertSame(TokenNormalizer::instance(), $this->parser->tokenNormalizer());
    }

    public function testConstructorTraitSupport()
    {
        Phake::when($this->isolator)->defined('T_TRAIT')->thenReturn(true);
        Phake::when($this->isolator)->constant('T_TRAIT')->thenReturn(111);
        $this->parser = new ResolutionContextParser(null, null, null, null, null, null, $this->isolator);

        $this->assertSame(111, Liberator::liberate($this->parser)->traitTokenType);
    }

    public function testRegularNamespaces()
    {
        $source = <<<'EOD'
<?php

    declare ( ticks = 1 ) ;

    namespace NamespaceA \ NamespaceB ;

    use ClassF ;

    use ClassG as ClassH ;

    use NamespaceD \ ClassI ;

    use NamespaceE \ ClassJ as ClassK ;

    use NamespaceF \ NamespaceG \ ClassL ;

    $object = new namespace \ ClassA ;

    interface InterfaceA
    {
        public function functionA ( ) ;
    }

    interface InterfaceB
    {
        public function functionB ( ) ;
        public function functionC ( ) ;
    }

    interface InterfaceC extends InterfaceA , InterfaceB
    {
    }

    $object = new namespace \ ClassA ;

    class ClassB
    {
    }

    class ClassC implements InterfaceA
    {
        public function functionA()
        {
        }
    }

    class ClassD implements InterfaceA , InterfaceB
    {
        public function functionA()
        {
        }

        public function functionB()
        {
        }

        public function functionC()
        {
        }
    }

    public function FunctionA(ClassA $a, ClassB $b = null, ClassC $C = null)
    {
    }

    public function FunctionB()
    {
    }

    const CONSTANT_A = 'CONSTANT_A_VALUE';
    const CONSTANT_B = CONSTANT_C;

    $object = new namespace \ ClassA ;

    namespace NamespaceC ;

    use ClassM ;

    use ClassN ;

    class ClassE
    {
    }

    interface InterfaceD
    {
    }

EOD;
        $expected = <<<'EOD'
Context at position (5, 5):

namespace NamespaceA\NamespaceB;

use ClassF;
use ClassG as ClassH;
use NamespaceD\ClassI;
use NamespaceE\ClassJ as ClassK;
use NamespaceF\NamespaceG\ClassL;

interface \NamespaceA\NamespaceB\InterfaceA;
interface \NamespaceA\NamespaceB\InterfaceB;
interface \NamespaceA\NamespaceB\InterfaceC;
class \NamespaceA\NamespaceB\ClassB;
class \NamespaceA\NamespaceB\ClassC;
class \NamespaceA\NamespaceB\ClassD;
function \NamespaceA\NamespaceB\FunctionA;
function \NamespaceA\NamespaceB\FunctionB;
const \NamespaceA\NamespaceB\CONSTANT_A;
const \NamespaceA\NamespaceB\CONSTANT_B;

Context at position (75, 5):

namespace NamespaceC;

use ClassM;
use ClassN;

class \NamespaceC\ClassE;
interface \NamespaceC\InterfaceD;

EOD;
        $actual = $this->parser->parseSource($source);

        $this->assertSame($expected, $this->renderContexts($actual));
    }

    public function testAlternateNamespaces()
    {
        $source = <<<'EOD'
<?php

    declare ( ticks = 1 ) ;

    namespace NamespaceA \ NamespaceB
    {
        use ClassF ;

        use ClassG as ClassH ;

        use NamespaceD \ ClassI ;

        use NamespaceE \ ClassJ as ClassK ;

        use NamespaceF \ NamespaceG \ ClassL ;

        $object = new namespace \ ClassA ;

        interface InterfaceA
        {
            public function functionA ( ) ;
        }

        interface InterfaceB
        {
            public function functionB ( ) ;
            public function functionC ( ) ;
        }

        interface InterfaceC extends InterfaceA , InterfaceB
        {
        }

        $object = new namespace \ ClassA ;

        class ClassB
        {
        }

        class ClassC implements InterfaceA
        {
            public function functionA()
            {
            }
        }

        class ClassD implements InterfaceA , InterfaceB
        {
            public function functionA()
            {
            }

            public function functionB()
            {
            }

            public function functionC()
            {
            }
        }

        function FunctionA(ClassA $a, ClassB $b = null, ClassC $C = null)
        {
        }

        function FunctionB()
        {
        }

        const CONSTANT_A = 'CONSTANT_A_VALUE';
        const CONSTANT_B = CONSTANT_C;

        $object = new namespace \ ClassA ;
    }

    namespace NamespaceC
    {
        use ClassM ;

        use ClassN ;

        class ClassE
        {
        }

        interface InterfaceD
        {
        }

        $object = new namespace \ ClassA ;
    }

    namespace
    {
        use ClassO ;

        use ClassP ;

        $object = new namespace \ ClassA ;

        class ClassQ
        {
        }

        interface InterfaceE
        {
        }

        function FunctionC()
        {
        }

        const CONSTANT_D = 'CONSTANT_D_VALUE';
    }

EOD;
        $expected = <<<'EOD'
Context at position (5, 5):

namespace NamespaceA\NamespaceB;

use ClassF;
use ClassG as ClassH;
use NamespaceD\ClassI;
use NamespaceE\ClassJ as ClassK;
use NamespaceF\NamespaceG\ClassL;

interface \NamespaceA\NamespaceB\InterfaceA;
interface \NamespaceA\NamespaceB\InterfaceB;
interface \NamespaceA\NamespaceB\InterfaceC;
class \NamespaceA\NamespaceB\ClassB;
class \NamespaceA\NamespaceB\ClassC;
class \NamespaceA\NamespaceB\ClassD;
function \NamespaceA\NamespaceB\FunctionA;
function \NamespaceA\NamespaceB\FunctionB;
const \NamespaceA\NamespaceB\CONSTANT_A;
const \NamespaceA\NamespaceB\CONSTANT_B;

Context at position (76, 5):

namespace NamespaceC;

use ClassM;
use ClassN;

class \NamespaceC\ClassE;
interface \NamespaceC\InterfaceD;

Context at position (93, 5):

use ClassO;
use ClassP;

class \ClassQ;
interface \InterfaceE;
function \FunctionC;
const \CONSTANT_D;

EOD;
        $actual = $this->parser->parseSource($source);

        $this->assertSame($expected, $this->renderContexts($actual));
    }

    public function testNoNamespace()
    {
        $source = <<<'EOD'
<?php

    declare ( ticks = 1 ) ;

    use ClassF ;

    use ClassG as ClassH ;

    use NamespaceD \ ClassI ;

    use NamespaceE \ ClassJ as ClassK ;

    use NamespaceF \ NamespaceG \ ClassL ;

    $object = new namespace \ ClassA ;

    interface InterfaceA
    {
        public function functionA ( ) ;
    }

    interface InterfaceB
    {
        public function functionB ( ) ;
        public function functionC ( ) ;
    }

    interface InterfaceC extends InterfaceA , InterfaceB
    {
    }

    class ClassB
    {
    }

    class ClassC implements InterfaceA
    {
        public function functionA()
        {
        }
    }

    class ClassD implements InterfaceA , InterfaceB
    {
        public function functionA()
        {
        }

        public function functionB()
        {
        }

        public function functionC()
        {
        }
    }

EOD;
        $expected = <<<'EOD'
Context at position (1, 1):

use ClassF;
use ClassG as ClassH;
use NamespaceD\ClassI;
use NamespaceE\ClassJ as ClassK;
use NamespaceF\NamespaceG\ClassL;

interface \InterfaceA;
interface \InterfaceB;
interface \InterfaceC;
class \ClassB;
class \ClassC;
class \ClassD;

EOD;
        $actual = $this->parser->parseSource($source);

        $this->assertSame($expected, $this->renderContexts($actual));
    }

    public function testNoUseStatements()
    {
        $source = <<<'EOD'
<?php

    declare ( ticks = 1 ) ;

    namespace NamespaceA \ NamespaceB ;

    $object = new namespace \ ClassA ;

    interface InterfaceA
    {
        public function functionA ( ) ;
    }

    interface InterfaceB
    {
        public function functionB ( ) ;
        public function functionC ( ) ;
    }

    interface InterfaceC extends InterfaceA , InterfaceB
    {
    }

    class ClassB
    {
    }

    class ClassC implements InterfaceA
    {
        public function functionA()
        {
        }
    }

    class ClassD implements InterfaceA , InterfaceB
    {
        public function functionA()
        {
        }

        public function functionB()
        {
        }

        public function functionC()
        {
        }
    }

EOD;
        $expected = <<<'EOD'
Context at position (5, 5):

namespace NamespaceA\NamespaceB;

interface \NamespaceA\NamespaceB\InterfaceA;
interface \NamespaceA\NamespaceB\InterfaceB;
interface \NamespaceA\NamespaceB\InterfaceC;
class \NamespaceA\NamespaceB\ClassB;
class \NamespaceA\NamespaceB\ClassC;
class \NamespaceA\NamespaceB\ClassD;

EOD;
        $actual = $this->parser->parseSource($source);

        $this->assertSame($expected, $this->renderContexts($actual));
    }

    public function testNoNamespaceOrUseStatements()
    {
        $source = <<<'EOD'
<?php

    declare ( ticks = 1 ) ;

    $object = new namespace \ ClassA ;

    interface InterfaceA
    {
        public function functionA ( ) ;
    }

    interface InterfaceB
    {
        public function functionB ( ) ;
        public function functionC ( ) ;
    }

    interface InterfaceC extends InterfaceA , InterfaceB
    {
    }

    class ClassB
    {
    }

    class ClassC implements InterfaceA
    {
        public function functionA()
        {
        }
    }

    class ClassD implements InterfaceA , InterfaceB
    {
        public function functionA()
        {
        }

        public function functionB()
        {
        }

        public function functionC()
        {
        }
    }

EOD;
        $expected = <<<'EOD'
Context at position (1, 1):

interface \InterfaceA;
interface \InterfaceB;
interface \InterfaceC;
class \ClassB;
class \ClassC;
class \ClassD;

EOD;
        $actual = $this->parser->parseSource($source);

        $this->assertSame($expected, $this->renderContexts($actual));
    }

    public function testNoClasses()
    {
        $source = <<<'EOD'
<?php

    declare ( ticks = 1 ) ;

    namespace NamespaceA \ NamespaceB
    {
        use ClassF ;

        use ClassG as ClassH ;

        use NamespaceD \ ClassI ;

        use NamespaceE \ ClassJ as ClassK ;

        use NamespaceF \ NamespaceG \ ClassL ;

        $object = new namespace \ ClassA ;
    }

    namespace NamespaceC
    {
        use ClassM ;

        use ClassN ;
    }

    namespace
    {
        use ClassO ;

        use ClassP ;
    }

EOD;
        $expected = <<<'EOD'
Context at position (5, 5):

namespace NamespaceA\NamespaceB;

use ClassF;
use ClassG as ClassH;
use NamespaceD\ClassI;
use NamespaceE\ClassJ as ClassK;
use NamespaceF\NamespaceG\ClassL;

Context at position (20, 5):

namespace NamespaceC;

use ClassM;
use ClassN;

Context at position (27, 5):

use ClassO;
use ClassP;

EOD;
        $actual = $this->parser->parseSource($source);

        $this->assertSame($expected, $this->renderContexts($actual));
    }

    public function testEmptySource()
    {
        $source = '';
        $expected = <<<'EOD'
Context at position (1, 1):

EOD;
        $actual = $this->parser->parseSource($source);

        $this->assertSame($expected, $this->renderContexts($actual));
    }

    public function testTraitSupport()
    {
        $this->parser = new ResolutionContextParser;
        $source = <<<'EOD'
<?php

    declare ( ticks = 1 ) ;

    namespace NamespaceA \ NamespaceB ;

    use ClassF ;

    use ClassG as ClassH ;

    use NamespaceD \ ClassI ;

    use NamespaceE \ ClassJ as ClassK ;

    use NamespaceF \ NamespaceG \ ClassL ;

    $object = new namespace \ ClassA ;

    interface InterfaceA
    {
        public function functionA ( ) ;
    }

    interface InterfaceB
    {
        public function functionB ( ) ;
        public function functionC ( ) ;
    }

    interface InterfaceC extends InterfaceA , InterfaceB
    {
    }

    trait TraitA
    {
    }

    trait TraitB
    {
    }

    trait TraitC
    {
        use TraitA ;

        use TraitB ;
    }

    class ClassB
    {
    }

    class ClassC implements InterfaceA
    {
        public function functionA()
        {
        }
    }

    class ClassD implements InterfaceA , InterfaceB
    {
        use TraitA ;

        use TraitB ;

        public function functionA()
        {
        }

        public function functionB()
        {
        }

        public function functionC()
        {
        }
    }

EOD;
        $expected = <<<'EOD'
Context at position (5, 5):

namespace NamespaceA\NamespaceB;

use ClassF;
use ClassG as ClassH;
use NamespaceD\ClassI;
use NamespaceE\ClassJ as ClassK;
use NamespaceF\NamespaceG\ClassL;

interface \NamespaceA\NamespaceB\InterfaceA;
interface \NamespaceA\NamespaceB\InterfaceB;
interface \NamespaceA\NamespaceB\InterfaceC;
trait \NamespaceA\NamespaceB\TraitA;
trait \NamespaceA\NamespaceB\TraitB;
trait \NamespaceA\NamespaceB\TraitC;
class \NamespaceA\NamespaceB\ClassB;
class \NamespaceA\NamespaceB\ClassC;
class \NamespaceA\NamespaceB\ClassD;

EOD;
        $actual = $this->parser->parseSource($source);

        $this->assertSame($expected, $this->renderContexts($actual));
    }

    public function testNamespaceAndTraitOnly()
    {
        $this->parser = new ResolutionContextParser;
        $source = <<<'EOD'
<?php

    namespace NamespaceA;

    trait TraitA
    {
    }

EOD;
        $expected = <<<'EOD'
Context at position (3, 5):

namespace NamespaceA;

trait \NamespaceA\TraitA;

EOD;
        $actual = $this->parser->parseSource($source);

        $this->assertSame($expected, $this->renderContexts($actual));
    }

    public function testNamespaceAndFunctionOnly()
    {
        $this->parser = new ResolutionContextParser;
        $source = <<<'EOD'
<?php

    namespace NamespaceA;

    function FunctionA
    {
    }

EOD;
        $expected = <<<'EOD'
Context at position (3, 5):

namespace NamespaceA;

function \NamespaceA\FunctionA;

EOD;
        $actual = $this->parser->parseSource($source);

        $this->assertSame($expected, $this->renderContexts($actual));
    }

    public function testNamespaceAndConstantOnly()
    {
        $this->parser = new ResolutionContextParser;
        $source = <<<'EOD'
<?php

    namespace NamespaceA;

    const CONSTANT_A = 'CONSTANT_A_VALUE';

EOD;
        $expected = <<<'EOD'
Context at position (3, 5):

namespace NamespaceA;

const \NamespaceA\CONSTANT_A;

EOD;
        $actual = $this->parser->parseSource($source);

        $this->assertSame($expected, $this->renderContexts($actual));
    }

    public function testUseStatementTypes()
    {
        $this->parser = new ResolutionContextParser;
        $source = <<<'EOD'
<?php

    use ClassF ;

    use ClassG as ClassH ;

    use NamespaceD \ ClassI ;

    use NamespaceE \ ClassJ as ClassK ;

    use NamespaceF \ NamespaceG \ ClassL ;

    use function FunctionA ;

    use function FunctionB as FunctionC ;

    use function NamespaceG \ FunctionD ;

    use function NamespaceH \ FunctionE as FunctionF ;

    use const CONSTANT_A ;

    use const CONSTANT_B as CONSTANT_C ;

    use const NamespaceI \ CONSTANT_D ;

    use const NamespaceJ \ CONSTANT_E as CONSTANT_F ;

EOD;
        $expected = <<<'EOD'
Context at position (1, 1):

use ClassF;
use ClassG as ClassH;
use NamespaceD\ClassI;
use NamespaceE\ClassJ as ClassK;
use NamespaceF\NamespaceG\ClassL;
use function FunctionA;
use function FunctionB as FunctionC;
use function NamespaceG\FunctionD;
use function NamespaceH\FunctionE as FunctionF;
use const CONSTANT_A;
use const CONSTANT_B as CONSTANT_C;
use const NamespaceI\CONSTANT_D;
use const NamespaceJ\CONSTANT_E as CONSTANT_F;

EOD;
        $actual = $this->parser->parseSource($source);

        $this->assertSame($expected, $this->renderContexts($actual));
    }

    public function testInstance()
    {
        $class = get_class($this->parser);
        $liberatedClass = Liberator::liberateClass($class);
        $liberatedClass->instance = null;
        $actual = $class::instance();

        $this->assertInstanceOf($class, $actual);
        $this->assertSame($actual, $class::instance());
    }

    protected function renderContexts(array $contexts)
    {
        $rendered = '';
        foreach ($contexts as $context) {
            if ('' !== $rendered) {
                $rendered .= "\n";
            }

            $rendered .= $this->renderContext($context);
        }

        return $rendered;
    }

    protected function renderContext(ParsedResolutionContextInterface $context)
    {
        $rendered = sprintf(
            "Context at position (%d, %d):\n",
            $context->lineNumber(),
            $context->columnNumber()
        );

        if (
            !$context->context()->primaryNamespace()->isRoot() ||
            count($context->context()->useStatements()) > 0
        ) {
            $rendered .= "\n";
        }

        $rendered .= $this->contextRenderer->renderContext($context->context());

        if (count($context->symbols()) > 0) {
            $rendered .= "\n";
        }

        foreach ($context->symbols() as $symbol) {
            $rendered .= $symbol->type()->value() . ' '. $symbol->symbol()->string() . ";\n";
        }

        return $rendered;
    }
}
