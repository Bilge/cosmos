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

use Eloquent\Cosmos\Resolution\Context\ResolutionContextVisitorInterface;
use Eloquent\Cosmos\Symbol\QualifiedSymbolInterface;
use Eloquent\Cosmos\Symbol\SymbolReferenceInterface;
use Eloquent\Cosmos\UseStatement\UseStatementInterface;
use Eloquent\Cosmos\UseStatement\UseStatementType;

/**
 * Represents a parsed use statement.
 */
class ParsedUseStatement extends AbstractParsedElement implements
    ParsedUseStatementInterface
{
    /**
     * Construct a new parsed use statement.
     *
     * @param UseStatementInterface        $useStatement The use statement.
     * @param ParserPositionInterface|null $position     The position.
     */
    public function __construct(
        UseStatementInterface $useStatement,
        ParserPositionInterface $position = null
    ) {
        parent::__construct($position);

        $this->useStatement = $useStatement;
    }

    /**
     * Get the use statement.
     *
     * @return UseStatementInterface The use statement.
     */
    public function useStatement()
    {
        return $this->useStatement;
    }

    /**
     * Get the symbol.
     *
     * @return QualifiedSymbolInterface The symbol.
     */
    public function symbol()
    {
        return $this->useStatement()->symbol();
    }

    /**
     * Get the alias for the symbol.
     *
     * @return SymbolReferenceInterface|null The alias, or null if no alias is in use.
     */
    public function alias()
    {
        return $this->useStatement()->alias();
    }

    /**
     * Get the effective alias for the symbol.
     *
     * @return SymbolReferenceInterface The alias, or the last atom of the symbol.
     */
    public function effectiveAlias()
    {
        return $this->useStatement()->effectiveAlias();
    }

    /**
     * Get the use statement type.
     *
     * @return UseStatementType The type.
     */
    public function type()
    {
        return $this->useStatement()->type();
    }

    /**
     * Generate a string representation of this use statement.
     *
     * @return string A string representation of this use statement.
     */
    public function string()
    {
        return $this->useStatement()->string();
    }

    /**
     * Generate a string representation of this use statement.
     *
     * @return string A string representation of this use statement.
     */
    public function __toString()
    {
        return strval($this->useStatement());
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
        return $visitor->visitUseStatement($this);
    }

    private $useStatement;
}
