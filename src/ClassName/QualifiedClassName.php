<?php

/*
 * This file is part of the Cosmos package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Cosmos\ClassName;

use Eloquent\Cosmos\ClassName\Exception\InvalidClassNameAtomException;
use Eloquent\Cosmos\ClassName\Factory\ClassNameFactory;
use Eloquent\Cosmos\ClassName\Factory\ClassNameFactoryInterface;
use Eloquent\Cosmos\ClassName\Normalizer\ClassNameNormalizer;
use Eloquent\Cosmos\ClassName\Normalizer\ClassNameNormalizerInterface;
use Eloquent\Cosmos\Resolution\ClassNameResolver;
use Eloquent\Cosmos\Resolution\ClassNameResolverInterface;
use Eloquent\Cosmos\Resolution\Context\ResolutionContextInterface;
use Eloquent\Cosmos\Resolution\Context\ResolutionContextVisitorInterface;
use Eloquent\Pathogen\AbsolutePath;
use ReflectionClass;

/**
 * Represents a fully qualified class name.
 */
class QualifiedClassName extends AbsolutePath implements
    QualifiedClassNameInterface
{
    /**
     * The character used to separate class name atoms.
     */
    const ATOM_SEPARATOR = '\\';

    /**
     * The character used to separate PEAR-style namespaces.
     */
    const EXTENSION_SEPARATOR = '_';

    /**
     * The regular expression used to validate class name atoms.
     */
    const CLASS_NAME_PATTERN = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

    /**
     * Creates a new qualified class name instance from its string
     * representation, regardless of whether it starts with a namespace
     * separator.
     *
     * This method emulates the manner in which class names are typically
     * interpreted at run time.
     *
     * @param string $className The string representation of the class name.
     *
     * @return QualifiedClassNameInterface The newly created qualified class name instance.
     */
    public static function fromRuntimeString($className)
    {
        return static::factory()->createRuntime($className);
    }

    /**
     * Get the class name of the supplied object.
     *
     * @param object $object The object.
     *
     * @return QualifiedClassNameInterface The object's qualified class name.
     */
    public static function fromObject($object)
    {
        return static::factory()->createFromObject($object);
    }

    /**
     * Get the class name of the supplied class or object reflector.
     *
     * @param ReflectionClass $reflector The reflector.
     *
     * @return QualifiedClassNameInterface The qualified class name.
     */
    public static function fromReflector(ReflectionClass $reflector)
    {
        return static::factory()->createFromReflector($reflector);
    }

    /**
     * Get the qualified class name representing the global namespace.
     *
     * @return QualifiedClassNameInterface The global namespace class name.
     */
    public static function globalNamespace()
    {
        return static::factory()->globalNamespace();
    }

    /**
     * Construct a new fully qualified class name.
     *
     * @param mixed<string> $atoms The class name atoms.
     */
    public function __construct($atoms)
    {
        parent::__construct($atoms);
    }

    /**
     * Get the last atom of this class name as a class name reference.
     *
     * If this class name is already a short class name reference, it will be
     * returned unaltered.
     *
     * @return ClassNameReferenceInterface The short class name.
     */
    public function shortName()
    {
        $atoms = $this->atoms();
        $numAtoms = count($atoms);

        return $this->createPath(array($atoms[$numAtoms - 1]), false);
    }

    /**
     * Get the first atom of this class name as a class name reference.
     *
     * If this class name is already a short class name reference, it will be
     * returned unaltered.
     *
     * @return ClassNameReferenceInterface The short class name.
     */
    public function firstAtomShortName()
    {
        $atoms = $this->atoms();
        $numAtoms = count($atoms);

        return $this->createPath(array($atoms[0]), false);
    }

    /**
     * Find the shortest class name that will resolve to this class name from
     * within the supplied resolution context.
     *
     * If this class is not a child of the primary namespace, and there are no
     * related use statements, this method will return a qualified class
     * name.
     *
     * @param ResolutionContextInterface $context The resolution context.
     *
     * @return ClassNameInterface The shortest class name.
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
        return $visitor->visitQualifiedClassName($this);
    }

    /**
     * Validates the supplied class name atom.
     *
     * @param string $atom The atom to validate.
     */
    protected function validateAtom($atom)
    {
        if (static::SELF_ATOM === $atom || static::PARENT_ATOM === $atom) {
            return;
        }

        if (!preg_match(static::CLASS_NAME_PATTERN, $atom)) {
            throw new InvalidClassNameAtomException($atom);
        }
    }

    /**
     * Get the class name factory.
     *
     * @return ClassNameFactoryInterface The class name factory.
     */
    protected static function factory()
    {
        return ClassNameFactory::instance();
    }

    /**
     * Get the class name normalizer.
     *
     * @return ClassNameNormalizerInterface The class name normalizer.
     */
    protected static function normalizer()
    {
        return ClassNameNormalizer::instance();
    }

    /**
     * Get the class name resolver.
     *
     * @return ClassNameResolverInterface The class name resolver.
     */
    protected static function resolver()
    {
        return ClassNameResolver::instance();
    }
}
