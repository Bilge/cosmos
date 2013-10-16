<?php

/*
 * This file is part of the Cosmos package.
 *
 * Copyright © 2013 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eloquent\Cosmos\ClassName;

/**
 * Represents a use statement.
 */
class UseStatement implements UseStatementInterface
{
    /**
     * Construct a new use statement.
     *
     * @param QualifiedClassNameInterface      $className The class name.
     * @param ClassNameReferenceInterface|null $alias     The alias for the class name.
     *
     * @throws Exception\InvalidClassNameAtomException If an invalid alias is supplied.
     */
    public function __construct(
        QualifiedClassNameInterface $className,
        ClassNameReferenceInterface $alias = null
    ) {
        $this->className = $className->normalize();

        if (null !== $alias) {
            $normalizedAlias = $alias->normalize();
            $aliasAtoms = $normalizedAlias->atoms();

            if (
                count($aliasAtoms) > 1 ||
                QualifiedClassName::SELF_ATOM === $aliasAtoms[0] ||
                QualifiedClassName::PARENT_ATOM === $aliasAtoms[0]
            ) {
                throw new Exception\InvalidClassNameAtomException(
                    $alias->string()
                );
            }

            $this->alias = $normalizedAlias;
        }
    }

    /**
     * Get the class name.
     *
     * @return QualifiedClassNameInterface The class name.
     */
    public function className()
    {
        return $this->className;
    }

    /**
     * Get the alias for the class name.
     *
     * @return ClassNameReferenceInterface|null The alias, or null if no alias is in use.
     */
    public function alias()
    {
        return $this->alias;
    }

    private $className;
    private $alias;
}