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

use Eloquent\Cosmos\ClassName\ClassNameReferenceInterface;
use Eloquent\Cosmos\ClassName\Factory\ClassNameFactory;
use Eloquent\Cosmos\ClassName\Factory\ClassNameFactoryInterface;
use Eloquent\Cosmos\ClassName\QualifiedClassNameInterface;
use Eloquent\Cosmos\UseStatement\UseStatementInterface;

/**
 * Represents a combined namespace and set of use statements.
 */
class ResolutionContext implements ResolutionContextInterface
{
    /**
     * Construct a new class name resolution context.
     *
     * @param QualifiedClassNameInterface|null  $primaryNamespace The namespace.
     * @param array<UseStatementInterface>|null $useStatements    The use statements.
     * @param ClassNameFactoryInterface|null    $factory          The class name factory to use.
     */
    public function __construct(
        QualifiedClassNameInterface $primaryNamespace = null,
        array $useStatements = null,
        ClassNameFactoryInterface $factory = null
    ) {
        if (null === $factory) {
            $factory = ClassNameFactory::instance();
        }
        if (null === $primaryNamespace) {
            $primaryNamespace = $factory->createFromAtoms(array(), true);
        }
        if (null === $useStatements) {
            $useStatements = array();
        }

        $this->primaryNamespace = $primaryNamespace;
        $this->useStatements = $useStatements;

        $this->index = $this->buildIndex();
    }

    /**
     * Get the namespace.
     *
     * @return QualifiedClassNameInterface The namespace.
     */
    public function primaryNamespace()
    {
        return $this->primaryNamespace;
    }

    /**
     * Get the use statements.
     *
     * @return array<UseStatementInterface> The use statements.
     */
    public function useStatements()
    {
        return $this->useStatements;
    }

    /**
     * Get the class name or namespace associated with the supplied short name.
     *
     * @param ClassNameReferenceInterface $shortName The short name.
     *
     * @return QualifiedClassNameInterface|null The class name / namespace, or null if no associated class name / namespace exists.
     */
    public function classNameByShortName(
        ClassNameReferenceInterface $shortName
    ) {
        $index = $this->index();
        $shortNameString = $shortName->atomAt(0);
        if (array_key_exists($shortNameString, $index)) {
            return $index[$shortNameString];
        }

        return null;
    }

    /**
     * Get an index for resolving class name references.
     *
     * The first time this method is called, the index will be built.
     *
     * @return array The index.
     */
    protected function index()
    {
        return $this->index;
    }

    /**
     * Builds the internal index used to resolve class name references.
     *
     * @return array The index.
     */
    protected function buildIndex()
    {
        $index = array();
        foreach ($this->useStatements() as $useStatement) {
            $index[$useStatement->effectiveAlias()->string()] =
                $useStatement->className();
        }

        return $index;
    }

    private $primaryNamespace;
    private $useStatements;
    private $index;
}
