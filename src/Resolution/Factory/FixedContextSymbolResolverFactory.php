<?php

/*
 * This file is part of the Cosmos package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Cosmos\Resolution\Factory;

use Eloquent\Cosmos\Exception\ReadException;
use Eloquent\Cosmos\Exception\UndefinedSymbolException;
use Eloquent\Cosmos\Resolution\Context\Factory\Exception\UndefinedResolutionContextException;
use Eloquent\Cosmos\Resolution\Context\Factory\ResolutionContextFactory;
use Eloquent\Cosmos\Resolution\Context\Factory\ResolutionContextFactoryInterface;
use Eloquent\Cosmos\Resolution\Context\Parser\ParserPositionInterface;
use Eloquent\Cosmos\Resolution\Context\ResolutionContextInterface;
use Eloquent\Cosmos\Resolution\FixedContextSymbolResolver;
use Eloquent\Cosmos\Resolution\SymbolResolver;
use Eloquent\Cosmos\Resolution\SymbolResolverInterface;
use Eloquent\Cosmos\Symbol\SymbolInterface;
use Eloquent\Pathogen\FileSystem\FileSystemPathInterface;
use Eloquent\Pathogen\Resolver\PathResolverInterface;
use ReflectionClass;
use ReflectionFunction;

/**
 * Creates fixed context symbol resolvers.
 */
class FixedContextSymbolResolverFactory implements
    FixedContextSymbolResolverFactoryInterface
{
    /**
     * Get a static instance of this factory.
     *
     * @return FixedContextSymbolResolverFactoryInterface The static factory.
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Construct a new fixed context symbol resolver factory.
     *
     * @param SymbolResolverInterface|null           $resolver       The internal symbol resolver to use.
     * @param ResolutionContextFactoryInterface|null $contextFactory The resolution context factory to use.
     */
    public function __construct(
        SymbolResolverInterface $resolver = null,
        ResolutionContextFactoryInterface $contextFactory = null
    ) {
        if (null === $resolver) {
            $resolver = SymbolResolver::instance();
        }
        if (null === $contextFactory) {
            $contextFactory = ResolutionContextFactory::instance();
        }

        $this->resolver = $resolver;
        $this->contextFactory = $contextFactory;
    }

    /**
     * Get the internal resolver.
     *
     * @return SymbolResolverInterface The internal resolver.
     */
    public function resolver()
    {
        return $this->resolver;
    }

    /**
     * Get the resolution context factory.
     *
     * @return ResolutionContextFactoryInterface The resolution context factory.
     */
    public function contextFactory()
    {
        return $this->contextFactory;
    }

    /**
     * Construct a new fixed context symbol resolver.
     *
     * @param ResolutionContextInterface|null $context The resolution context.
     *
     * @return PathResolverInterface The newly created resolver.
     */
    public function create(ResolutionContextInterface $context = null)
    {
        return new FixedContextSymbolResolver($context, $this->resolver());
    }

    /**
     * Create a new fixed context symbol resolver for the supplied object.
     *
     * @param object $object The object.
     *
     * @return PathResolverInterface The newly created resolver.
     * @throws ReadException         If the source code cannot be read.
     */
    public function createFromObject($object)
    {
        return $this
            ->create($this->contextFactory()->createFromObject($object));
    }

    /**
     * Create a new fixed context symbol resolver for the supplied class,
     * interface, or trait symbol.
     *
     * @param SymbolInterface|string $symbol The symbol.
     *
     * @return PathResolverInterface    The newly created resolver.
     * @throws ReadException            If the source code cannot be read.
     * @throws UndefinedSymbolException If the symbol does not exist, or cannot be found in the source code.
     */
    public function createFromSymbol($symbol)
    {
        return $this
            ->create($this->contextFactory()->createFromSymbol($symbol));
    }

    /**
     * Create a new fixed context symbol resolver for the supplied function
     * symbol.
     *
     * @param SymbolInterface|string $symbol The symbol.
     *
     * @return PathResolverInterface    The newly created resolver.
     * @throws ReadException            If the source code cannot be read.
     * @throws UndefinedSymbolException If the symbol does not exist, or cannot be found in the source code.
     */
    public function createFromFunctionSymbol($symbol)
    {
        return $this->create(
            $this->contextFactory()->createFromFunctionSymbol($symbol)
        );
    }

    /**
     * Create a new fixed context symbol resolver for the supplied class or
     * object reflector.
     *
     * @param ReflectionClass $class The class or object reflector.
     *
     * @return PathResolverInterface    The newly created resolver.
     * @throws ReadException            If the source code cannot be read.
     * @throws UndefinedSymbolException If the symbol cannot be found in the source code.
     */
    public function createFromClass(ReflectionClass $class)
    {
        return $this->create($this->contextFactory()->createFromClass($class));
    }

    /**
     * Create a new fixed context symbol resolver for the supplied function
     * reflector.
     *
     * @param ReflectionFunction $function The function reflector.
     *
     * @return PathResolverInterface    The newly created resolver.
     * @throws ReadException            If the source code cannot be read.
     * @throws UndefinedSymbolException If the symbol cannot be found in the source code.
     */
    public function createFromFunction(ReflectionFunction $function)
    {
        return $this
            ->create($this->contextFactory()->createFromFunction($function));
    }

    /**
     * Create a new fixed context symbol resolver for the first context found in
     * a file.
     *
     * @param FileSystemPathInterface|string $path The path.
     *
     * @return PathResolverInterface The newly created resolver.
     * @throws ReadException         If the source code cannot be read.
     */
    public function createFromFile($path)
    {
        return $this->create($this->contextFactory()->createFromFile($path));
    }

    /**
     * Create a new fixed context symbol resolver for the context found at the
     * specified index in a file.
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
        return $this->create(
            $this->contextFactory()->createFromFileByIndex($path, $index)
        );
    }

    /**
     * Create a new fixed context symbol resolver for the context found at the
     * specified position in a file.
     *
     * @param FileSystemPathInterface|string $path     The path.
     * @param ParserPositionInterface        $position The position.
     *
     * @return PathResolverInterface The newly created resolver.
     * @throws ReadException         If the source code cannot be read.
     */
    public function createFromFileByPosition(
        $path,
        ParserPositionInterface $position
    ) {
        return $this->create(
            $this->contextFactory()->createFromFileByPosition($path, $position)
        );
    }

    /**
     * Create a new fixed context symbol resolver for the first context found in
     * a stream.
     *
     * @param stream                              $stream The stream.
     * @param FileSystemPathInterface|string|null $path   The path, if known.
     *
     * @return PathResolverInterface The newly created resolver.
     * @throws ReadException         If the source code cannot be read.
     */
    public function createFromStream($stream, $path = null)
    {
        return $this
            ->create($this->contextFactory()->createFromStream($stream, $path));
    }

    /**
     * Create a new fixed context symbol resolver for the context found at the
     * specified index in a stream.
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
        return $this->create(
            $this->contextFactory()
                ->createFromStreamByIndex($stream, $index, $path)
        );
    }

    /**
     * Create a new fixed context symbol resolver for the context found at the
     * specified position in a stream.
     *
     * @param stream                              $stream   The stream.
     * @param ParserPositionInterface             $position The position.
     * @param FileSystemPathInterface|string|null $path     The path, if known.
     *
     * @return PathResolverInterface The newly created resolver.
     * @throws ReadException         If the source code cannot be read.
     */
    public function createFromStreamByPosition(
        $stream,
        ParserPositionInterface $position,
        $path = null
    ) {
        return $this->create(
            $this->contextFactory()
                ->createFromStreamByPosition($stream, $position, $path)
        );
    }

    private static $instance;
    private $resolver;
    private $contextFactory;
}
