<?php

/*
 * This file is part of the Cosmos package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Cosmos\Resolution\Context\Factory;

use Eloquent\Cosmos\Exception\ReadException;
use Eloquent\Cosmos\Exception\UndefinedSymbolException;
use Eloquent\Cosmos\Resolution\Context\Parser\ParsedSymbolInterface;
use Eloquent\Cosmos\Resolution\Context\Parser\ParserPositionInterface;
use Eloquent\Cosmos\Resolution\Context\Parser\ResolutionContextParser;
use Eloquent\Cosmos\Resolution\Context\Parser\ResolutionContextParserInterface;
use Eloquent\Cosmos\Resolution\Context\ResolutionContext;
use Eloquent\Cosmos\Resolution\Context\ResolutionContextInterface;
use Eloquent\Cosmos\Symbol\Factory\SymbolFactory;
use Eloquent\Cosmos\Symbol\Factory\SymbolFactoryInterface;
use Eloquent\Cosmos\Symbol\QualifiedSymbolInterface;
use Eloquent\Cosmos\Symbol\SymbolInterface;
use Eloquent\Cosmos\Symbol\SymbolType;
use Eloquent\Cosmos\UseStatement\UseStatementInterface;
use Eloquent\Pathogen\FileSystem\FileSystemPath;
use Icecave\Isolator\Isolator;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionObject;

/**
 * Creates symbol resolution contexts.
 */
class ResolutionContextFactory implements ResolutionContextFactoryInterface
{
    /**
     * Get a static instance of this factory.
     *
     * @return ResolutionContextFactoryInterface The static factory.
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Construct a new symbol resolution context factory.
     *
     * @param SymbolFactoryInterface|null           $symbolFactory The symbol factory to use.
     * @param ResolutionContextParserInterface|null $contextParser The context parser to use.
     * @param Isolator|null                         $isolator      The isolator to use.
     */
    public function __construct(
        SymbolFactoryInterface $symbolFactory = null,
        ResolutionContextParserInterface $contextParser = null,
        Isolator $isolator = null
    ) {
        if (null === $symbolFactory) {
            $symbolFactory = SymbolFactory::instance();
        }
        $isolator = Isolator::get($isolator);
        if (null === $contextParser) {
            $contextParser = new ResolutionContextParser(
                $symbolFactory,
                null,
                null,
                null,
                $this,
                null,
                $isolator
            );
        }

        $this->symbolFactory = $symbolFactory;
        $this->contextParser = $contextParser;
        $this->isolator = $isolator;
    }

    /**
     * Get the symbol factory.
     *
     * @return SymbolFactoryInterface The symbol factory.
     */
    public function symbolFactory()
    {
        return $this->symbolFactory;
    }

    /**
     * Get the resolution context parser.
     *
     * @return ResolutionContextParserInterface The resolution context parser.
     */
    public function contextParser()
    {
        return $this->contextParser;
    }

    /**
     * Create a new symbol resolution context.
     *
     * @param QualifiedSymbolInterface|null     $primaryNamespace The namespace.
     * @param array<UseStatementInterface>|null $useStatements    The use statements.
     *
     * @return ResolutionContextInterface The newly created resolution context.
     */
    public function create(
        QualifiedSymbolInterface $primaryNamespace = null,
        array $useStatements = null
    ) {
        return new ResolutionContext(
            $primaryNamespace,
            $useStatements,
            $this->symbolFactory()
        );
    }

    /**
     * Create a new symbol resolution context for the supplied object.
     *
     * @param object $object The object.
     *
     * @return ResolutionContextInterface The newly created resolution context.
     * @throws ReadException              If the source code cannot be read.
     */
    public function createFromObject($object)
    {
        return $this->createFromClass(new ReflectionObject($object));
    }

    /**
     * Create a new symbol resolution context for the supplied class, interface,
     * or trait symbol.
     *
     * @param SymbolInterface|string $symbol The symbol.
     *
     * @return ResolutionContextInterface The newly created resolution context.
     * @throws ReadException              If the source code cannot be read.
     * @throws UndefinedSymbolException   If the symbol does not exist, or cannot be found in the source code.
     */
    public function createFromSymbol($symbol)
    {
        if ($symbol instanceof SymbolInterface) {
            $symbol = $symbol->string();
        }

        try {
            $class = new ReflectionClass($symbol);
        } catch (ReflectionException $e) {
            throw new UndefinedSymbolException(
                $this->symbolFactory()->createRuntime($symbol),
                SymbolType::CLA55(),
                $e
            );
        }

        return $this->createFromClass($class);
    }

    /**
     * Create a new symbol resolution context for the supplied function symbol.
     *
     * @param SymbolInterface|string $symbol The symbol.
     *
     * @return ResolutionContextInterface The newly created resolution context.
     * @throws ReadException              If the source code cannot be read.
     * @throws UndefinedSymbolException   If the symbol does not exist, or cannot be found in the source code.
     */
    public function createFromFunctionSymbol($symbol)
    {
        if ($symbol instanceof SymbolInterface) {
            $symbol = $symbol->string();
        }

        try {
            $function = new ReflectionFunction($symbol);
        } catch (ReflectionException $e) {
            throw new UndefinedSymbolException(
                $this->symbolFactory()->createRuntime($symbol),
                SymbolType::FUNCT1ON(),
                $e
            );
        }

        return $this->createFromFunction($function);
    }

    /**
     * Create a new symbol resolution context for the supplied class or object
     * reflector.
     *
     * @param ReflectionClass $class The class or object reflector.
     *
     * @return ResolutionContextInterface The newly created resolution context.
     * @throws ReadException              If the source code cannot be read.
     * @throws UndefinedSymbolException   If the symbol cannot be found in the source code.
     */
    public function createFromClass(ReflectionClass $class)
    {
        if (false === $class->getFileName()) {
            return $this->create();
        }

        $symbol = '\\' . $class->getName();

        $context = $this->findBySymbolPredicate(
            $this->readFile($class->getFileName()),
            function (ParsedSymbolInterface $parsedSymbol) use ($symbol) {
                return $parsedSymbol->symbol()->string() === $symbol &&
                    $parsedSymbol->type()->isType();
            }
        );

        if (null === $context) {
            throw new UndefinedSymbolException(
                $this->symbolFactory()->create($symbol),
                SymbolType::CLA55()
            );
        }

        return $context;
    }

    /**
     * Create a new symbol resolution context for the supplied function
     * reflector.
     *
     * @param ReflectionFunction $function The function reflector.
     *
     * @return ResolutionContextInterface The newly created resolution context.
     * @throws ReadException              If the source code cannot be read.
     * @throws UndefinedSymbolException   If the symbol cannot be found in the source code.
     */
    public function createFromFunction(ReflectionFunction $function)
    {
        if (false === $function->getFileName()) {
            return $this->create();
        }

        $symbol = '\\' . $function->getName();

        $context = $this->findBySymbolPredicate(
            $this->readFile($function->getFileName()),
            function (ParsedSymbolInterface $parsedSymbol) use ($symbol) {
                return $parsedSymbol->symbol()->string() === $symbol &&
                    SymbolType::FUNCT1ON() === $parsedSymbol->type();
            }
        );

        if (null === $context) {
            throw new UndefinedSymbolException(
                $this->symbolFactory()->create($symbol),
                SymbolType::FUNCT1ON()
            );
        }

        return $context;
    }

    /**
     * Create the first context found in a file.
     *
     * @param FileSystemPathInterface|string $path The path.
     *
     * @return ResolutionContextInterface The newly created resolution context.
     * @throws ReadException              If the source code cannot be read.
     */
    public function createFromFile($path)
    {
        return $this->createFromFileByIndex($path, 0, $path);
    }

    /**
     * Create the context found at the specified index in a file.
     *
     * @param FileSystemPathInterface|string $path  The path.
     * @param integer                        $index The index.
     *
     * @return ResolutionContextInterface          The newly created resolution context.
     * @throws ReadException                       If the source code cannot be read.
     * @throws UndefinedResolutionContextException If there is no resolution context at the specified index.
     */
    public function createFromFileByIndex($path, $index)
    {
        return $this->findByIndex($this->readFile($path), $index, $path);
    }

    /**
     * Create the context found at the specified position in a file.
     *
     * @param FileSystemPathInterface|string $path     The path.
     * @param ParserPositionInterface        $position The position.
     *
     * @return ResolutionContextInterface The newly created resolution context.
     * @throws ReadException              If the source code cannot be read.
     */
    public function createFromFileByPosition(
        $path,
        ParserPositionInterface $position
    ) {
        return $this->findByPosition($this->readFile($path), $position);
    }

    /**
     * Create the first context found in a stream.
     *
     * @param stream                              $stream The stream.
     * @param FileSystemPathInterface|string|null $path   The path, if known.
     *
     * @return ResolutionContextInterface The newly created resolution context.
     * @throws ReadException              If the source code cannot be read.
     */
    public function createFromStream($stream, $path = null)
    {
        return $this->createFromStreamByIndex($stream, 0, $path);
    }

    /**
     * Create the context found at the specified index in a stream.
     *
     * @param stream                              $stream The stream.
     * @param integer                             $index  The index.
     * @param FileSystemPathInterface|string|null $path   The path, if known.
     *
     * @return ResolutionContextInterface          The newly created resolution context.
     * @throws ReadException                       If the source code cannot be read.
     * @throws UndefinedResolutionContextException If there is no resolution context at the specified index.
     */
    public function createFromStreamByIndex($stream, $index, $path = null)
    {
        return $this
            ->findByIndex($this->readStream($stream, $path), $index, $path);
    }

    /**
     * Create the context found at the specified position in a stream.
     *
     * @param stream                              $stream   The stream.
     * @param ParserPositionInterface             $position The position.
     * @param FileSystemPathInterface|string|null $path     The path, if known.
     *
     * @return ResolutionContextInterface The newly created resolution context.
     * @throws ReadException              If the source code cannot be read.
     */
    public function createFromStreamByPosition(
        $stream,
        ParserPositionInterface $position,
        $path = null
    ) {
        return $this
            ->findByPosition($this->readStream($stream, $path), $position);
    }

    private function findBySymbolPredicate($source, $predicate)
    {
        $contexts = $this->contextParser()->parseSource($source);

        $context = null;
        foreach ($contexts as $parsedContext) {
            foreach ($parsedContext->symbols() as $parsedSymbol) {
                if ($predicate($parsedSymbol)) {
                    $context = $parsedContext->context();

                    break 2;
                }
            }
        }

        return $context;
    }

    private function findByIndex($source, $index, $path = null)
    {
        $contexts = $this->contextParser()->parseSource($source);

        if (!array_key_exists($index, $contexts)) {
            if (is_string($path)) {
                $path = FileSystemPath::fromString($path);
            }

            throw new UndefinedResolutionContextException($path, $index);
        }

        return $contexts[$index]->context();
    }

    private function findByPosition($source, ParserPositionInterface $position)
    {
        $contexts = $this->contextParser()->parseSource($source);

        $context = null;
        foreach ($contexts as $parsedContext) {
            if ($this->positionIsAfter($parsedContext->position(), $position)) {
                break;
            }

            $context = $parsedContext->context();
        }

        return $context;
    }

    private function readFile($path)
    {
        $stream = @$this->isolator()->fopen($path, 'rb');
        if (false === $stream) {
            $lastError = $this->isolator()->error_get_last();
            if (is_string($path)) {
                $path = FileSystemPath::fromString($path);
            }

            throw new ReadException($lastError['message'], $path);
        }

        $error = null;
        try {
            $source = $this->readStream($stream, $path);
        } catch (ReadException $error) {
            // re-throw after cleanup
        }

        $this->isolator()->fclose($stream);

        if ($error) {
            throw $error;
        }

        return $source;
    }

    private function readStream($stream, $path = null)
    {
        $source = @stream_get_contents($stream);
        if (false === $source) {
            $lastError = $this->isolator()->error_get_last();
            if (is_string($path)) {
                $path = FileSystemPath::fromString($path);
            }

            throw new ReadException($lastError['message'], $path);
        }

        return $source;
    }

    private function positionIsAfter(
        ParserPositionInterface $left,
        ParserPositionInterface $right
    ) {
        $lineCompare = $left->line() - $right->line();

        if ($lineCompare > 0) {
            return true;
        }
        if ($lineCompare < 0) {
            return false;
        }

        return $left->column() >= $right->column();
    }

    /**
     * Get the isolator.
     *
     * @return Isolator The isolator.
     */
    protected function isolator()
    {
        return $this->isolator;
    }

    private static $instance;
    private $symbolFactory;
    private $contextParser;
    private $isolator;
}
