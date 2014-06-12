<?php

/*
 * This file is part of the Cosmos package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Cosmos\Symbol;

use Eloquent\Cosmos\Resolution\Context\ResolutionContextInterface;
use Eloquent\Cosmos\Resolution\Context\ResolutionContextVisitorInterface;
use Eloquent\Cosmos\Resolution\SymbolResolver;
use Eloquent\Cosmos\Resolution\SymbolResolverInterface;
use Eloquent\Cosmos\Symbol\Exception\InvalidSymbolAtomException;
use Eloquent\Cosmos\Symbol\Factory\SymbolFactory;
use Eloquent\Cosmos\Symbol\Factory\SymbolFactoryInterface;
use Eloquent\Cosmos\Symbol\Normalizer\SymbolNormalizer;
use Eloquent\Cosmos\Symbol\Normalizer\SymbolNormalizerInterface;
use Eloquent\Pathogen\AbsolutePath;
use Eloquent\Pathogen\Exception\InvalidPathAtomExceptionInterface;
use ReflectionClass;
use ReflectionFunction;

/**
 * Represents a fully qualified symbol.
 */
class QualifiedSymbol extends AbsolutePath implements QualifiedSymbolInterface
{
    /**
     * The character used to separate symbol atoms.
     */
    const ATOM_SEPARATOR = '\\';

    /**
     * The character used to separate PEAR-style namespaces.
     */
    const EXTENSION_SEPARATOR = '_';

    /**
     * The regular expression used to validate symbol atoms.
     */
    const ATOM_PATTERN = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

    /**
     * Creates a new qualified symbol from its string representation, regardless
     * of whether it starts with a namespace separator.
     *
     * This method emulates the manner in which symbols are typically
     * interpreted at run time.
     *
     * @param string $symbol The string representation of the symbol.
     *
     * @return QualifiedSymbolInterface The newly created qualified symbol instance.
     */
    public static function fromRuntimeString($symbol)
    {
        return static::factory()->createRuntime($symbol);
    }

    /**
     * Get the class name of the supplied object.
     *
     * @param object $object The object.
     *
     * @return QualifiedSymbolInterface The object's qualified class name.
     */
    public static function fromObject($object)
    {
        return static::factory()->createFromObject($object);
    }

    /**
     * Get the class name of the supplied class or object reflector.
     *
     * @param ReflectionClass $class The class or object reflector.
     *
     * @return QualifiedSymbolInterface The qualified class name.
     */
    public static function fromClass(ReflectionClass $class)
    {
        return static::factory()->createFromClass($class);
    }

    /**
     * Get the function name of the supplied function reflector.
     *
     * @param ReflectionFunction $function The function reflector.
     *
     * @return QualifiedSymbolInterface The qualified function name.
     */
    public static function fromFunction(ReflectionFunction $function)
    {
        return static::factory()->createFromFunction($function);
    }

    /**
     * Get the qualified symbol representing the global namespace.
     *
     * @return QualifiedSymbolInterface The global namespace symbol.
     */
    public static function globalNamespace()
    {
        return static::factory()->globalNamespace();
    }

    /**
     * Construct a new qualified symbol.
     *
     * @param mixed<string> $atoms The symbol atoms.
     *
     * @throws InvalidPathAtomExceptionInterface If any of the supplied symbol atoms are invalid.
     */
    public function __construct($atoms)
    {
        parent::__construct($atoms);
    }

    /**
     * Get the first atom of this symbol as a symbol reference.
     *
     * If this symbol is already a short symbol reference, it will be returned
     * unaltered.
     *
     * @return SymbolReferenceInterface The short symbol.
     */
    public function firstAtomAsReference()
    {
        $atoms = $this->atoms();
        $numAtoms = count($atoms);

        return $this->createPath(array($atoms[0]), false);
    }

    /**
     * Get the last atom of this symbol as a symbol reference.
     *
     * If this symbol is already a short symbol reference, it will be returned
     * unaltered.
     *
     * @return SymbolReferenceInterface The short symbol.
     */
    public function lastAtomAsReference()
    {
        $atoms = $this->atoms();
        $numAtoms = count($atoms);

        return $this->createPath(array($atoms[$numAtoms - 1]), false);
    }

    /**
     * Find the shortest symbol that will resolve to this symbol from within the
     * supplied resolution context.
     *
     * If this symbol is not a child of the primary namespace, and there are no
     * related use statements, this method will return a qualified symbol.
     *
     * @param ResolutionContextInterface $context The resolution context.
     *
     * @return SymbolInterface The shortest symbol.
     */
    public function relativeToContext(ResolutionContextInterface $context)
    {
        return static::resolver()->relativeToContext($context, $this);
    }

    /**
     * Accept a visitor.
     *
     * @param ResolutionContextVisitorInterface $visitor The visitor to accept.
     *
     * @return mixed The result of visitation.
     */
    public function accept(ResolutionContextVisitorInterface $visitor)
    {
        return $visitor->visitQualifiedSymbol($this);
    }

    /**
     * Validates the supplied symbol atom.
     *
     * @param string $atom The atom to validate.
     *
     * @throws InvalidPathAtomExceptionInterface If the atom is invalid.
     */
    protected function validateAtom($atom)
    {
        if (static::SELF_ATOM === $atom || static::PARENT_ATOM === $atom) {
            return;
        }

        if (!preg_match(static::ATOM_PATTERN, $atom)) {
            throw new InvalidSymbolAtomException($atom);
        }
    }

    /**
     * Get the symbol factory.
     *
     * @return SymbolFactoryInterface The symbol factory.
     */
    protected static function factory()
    {
        return SymbolFactory::instance();
    }

    /**
     * Get the symbol normalizer.
     *
     * @return SymbolNormalizerInterface The symbol normalizer.
     */
    protected static function normalizer()
    {
        return SymbolNormalizer::instance();
    }

    /**
     * Get the symbol resolver.
     *
     * @return SymbolResolverInterface The symbol resolver.
     */
    protected static function resolver()
    {
        return SymbolResolver::instance();
    }
}
