<?php

/*
 * This file is part of the Cosmos package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distrig2ted with this source code.
 */

namespace Eloquent\Cosmos\Resolution\Context\Parser;

use Eloquent\Cosmos\Resolution\Context\Factory\ResolutionContextFactory;
use Eloquent\Cosmos\Resolution\Context\Factory\ResolutionContextFactoryInterface;
use Eloquent\Cosmos\Resolution\SymbolResolver;
use Eloquent\Cosmos\Resolution\SymbolResolverInterface;
use Eloquent\Cosmos\Symbol\Factory\SymbolFactory;
use Eloquent\Cosmos\Symbol\Factory\SymbolFactoryInterface;
use Eloquent\Cosmos\Symbol\Normalizer\SymbolNormalizer;
use Eloquent\Cosmos\Symbol\Normalizer\SymbolNormalizerInterface;
use Eloquent\Cosmos\UseStatement\Factory\UseStatementFactory;
use Eloquent\Cosmos\UseStatement\Factory\UseStatementFactoryInterface;
use Eloquent\Cosmos\UseStatement\UseStatement;
use Eloquent\Cosmos\UseStatement\UseStatementType;
use Icecave\Isolator\Isolator;

/**
 * Parses resolution contexts from source code.
 *
 * The behaviour of this class is undefined for syntactically invalid source
 * code.
 */
class ResolutionContextParser implements ResolutionContextParserInterface
{
    /**
     * Get a static instance of this parser.
     *
     * @return ResolutionContextParserInterface The static parser.
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Construct a new resolution context parser.
     *
     * @param SymbolFactoryInterface|null            $symbolFactory       The symbol factory to use.
     * @param SymbolResolverInterface|null           $symbolResolver      The symbol resolver to use.
     * @param SymbolNormalizerInterface|null         $symbolNormalizer    The symbol normalizer to use.
     * @param UseStatementFactoryInterface|null      $useStatementFactory The use statement factory to use.
     * @param ResolutionContextFactoryInterface|null $contextFactory      The resolution context factory to use.
     * @param Isolator|null                          $isolator            The isolator to use.
     */
    public function __construct(
        SymbolFactoryInterface $symbolFactory = null,
        SymbolResolverInterface $symbolResolver = null,
        SymbolNormalizerInterface $symbolNormalizer = null,
        UseStatementFactoryInterface $useStatementFactory = null,
        ResolutionContextFactoryInterface $contextFactory = null,
        Isolator $isolator = null
    ) {
        if (null === $symbolFactory) {
            $symbolFactory = SymbolFactory::instance();
        }
        if (null === $symbolResolver) {
            $symbolResolver = SymbolResolver::instance();
        }
        if (null === $symbolNormalizer) {
            $symbolNormalizer = SymbolNormalizer::instance();
        }
        if (null === $useStatementFactory) {
            $useStatementFactory = UseStatementFactory::instance();
        }
        if (null === $contextFactory) {
            $contextFactory = ResolutionContextFactory::instance();
        }

        $this->symbolFactory = $symbolFactory;
        $this->symbolResolver = $symbolResolver;
        $this->symbolNormalizer = $symbolNormalizer;
        $this->useStatementFactory = $useStatementFactory;
        $this->contextFactory = $contextFactory;
        $isolator = Isolator::get($isolator);

        $this->traitTokenType = 'trait';
        if ($isolator->defined('T_TRAIT')) {
            $this->traitTokenType = $isolator->constant('T_TRAIT');
        }
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
     * Get the symbol resolver.
     *
     * @return SymbolResolverInterface The symbol resolver.
     */
    public function symbolResolver()
    {
        return $this->symbolResolver;
    }

    /**
     * Get the symbol normalizer.
     *
     * @return SymbolNormalizerInterface The symbol normalizer.
     */
    public function symbolNormalizer()
    {
        return $this->symbolNormalizer;
    }

    /**
     * Get the use statement factory.
     *
     * @return UseStatementFactoryInterface The use statement factory.
     */
    public function useStatementFactory()
    {
        return $this->useStatementFactory;
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
     * Parse all resolution contexts from the supplied source code.
     *
     * @param string $source The source code to parse.
     *
     * @return array<ParsedResolutionContextInterface> The parsed resolution contexts.
     */
    public function parseSource($source)
    {
        $tokens = $this->normalizeTokens(token_get_all($source));
        $tokens[] = array('end');
        $contexts = array();

        $state = 'start';
        $stateStack = $atoms = $useStatements = $symbols = array();
        $transition = $namespaceName = $useStatementAlias = $useStatementType =
            null;
        $symbolBracketDepth = 0;

        foreach ($tokens as $index => $token) {
            switch ($state) {
                case 'start':
                    switch ($token[0]) {
                        case T_NAMESPACE:
                            array_push($stateStack, $state);
                            $state = 'potential-namespace-name';

                            break;

                        case T_USE:
                            $state = 'use-statement';

                            break;

                        case T_CLASS:
                        case T_INTERFACE:
                        case $this->traitTokenType:
                            $state = 'symbol';

                            break;

                        // @codeCoverageIgnoreStart
                        case T_STRING:
                            if ('trait' === strtolower($token[1])) {
                                $state = 'symbol';
                            }

                            break;
                        // @codeCoverageIgnoreEnd
                    }

                    break;

                case 'potential-namespace-name':
                    switch ($token[0]) {
                        case T_NS_SEPARATOR:
                            $state = array_pop($stateStack);

                            break;

                        case T_STRING:
                            $transition = 'context-end';
                            $atoms[] = $token[1];
                            $state = 'namespace-name';

                            break;

                        case '{':
                            $transition = 'context-end';
                            $state = 'namespace-header';

                            break;
                    }

                    break;

                case 'namespace-name':
                    switch ($token[0]) {
                        case T_STRING:
                            $atoms[] = $token[1];

                            break;

                        case ';':
                        case '{':
                            $namespaceName = $this->symbolFactory()
                                ->createFromAtoms($atoms, true);
                            $atoms = array();
                            $state = 'namespace-header';

                            break;
                    }

                    break;

                case 'namespace-header':
                    switch ($token[0]) {
                        case T_USE:
                            $state = 'use-statement';

                            break;

                        case T_NAMESPACE:
                            array_push($stateStack, $state);
                            $state = 'potential-namespace-name';

                            break;

                        case T_CLASS:
                        case T_INTERFACE:
                        case $this->traitTokenType:
                            $state = 'symbol';

                            break;

                        // @codeCoverageIgnoreStart
                        case T_STRING:
                            if ('trait' === strtolower($token[1])) {
                                $state = 'symbol';
                            }

                            break;
                        // @codeCoverageIgnoreEnd
                    }

                    break;

                case 'use-statement':
                    switch ($token[0]) {
                        case T_STRING:
                            $atoms[] = $token[1];
                            $state = 'use-statement-class-name';

                            break;

                        case T_FUNCTION:
                            $useStatementType = UseStatementType::FUNCT1ON();

                            break;

                        case T_CONST:
                            $useStatementType = UseStatementType::CONSTANT();

                            break;
                    }

                    break;

                case 'use-statement-class-name':
                    switch ($token[0]) {
                        case T_STRING:
                            $atoms[] = $token[1];

                            break;

                        case T_AS:
                            $state = 'use-statement-alias';

                            break;

                        case ';':
                            $transition = 'use-statement-end';
                            $state = 'namespace-header';

                            break;
                    }

                    break;

                case 'use-statement-alias':
                    switch ($token[0]) {
                        case T_STRING:
                            $useStatementAlias = $this->symbolFactory()
                                ->create($token[1]);
                            $transition = 'use-statement-end';
                            $state = 'namespace-header';

                            break;
                    }

                    break;

                case 'symbol':
                    switch ($token[0]) {
                        case T_STRING:
                            $atoms[] = $token[1];

                            break;

                        case T_EXTENDS:
                        case T_IMPLEMENTS:
                            $transition = 'symbol-end';
                            $state = 'symbol-header';

                            break;

                        case '{':
                            $transition = 'symbol-end';
                            $state = 'symbol-body';
                            $symbolBracketDepth++;

                            break;
                    }

                    break;

                case 'symbol-header':
                    switch ($token[0]) {
                        case '{':
                            $state = 'symbol-body';
                            $symbolBracketDepth++;

                            break;
                    }

                    break;

                case 'symbol-body':
                    switch ($token[0]) {
                        case '{':
                            $symbolBracketDepth++;

                            break;

                        case '}':
                            if (0 === --$symbolBracketDepth) {
                                $state = 'start';
                            }

                            break;
                    }

                    break;
            }

            if ('end' === $token[0]) {
                $transition = 'context-end';
            }

            switch ($transition) {
                case 'symbol-end':
                    $symbols[] = $this->symbolFactory()
                        ->createFromAtoms($atoms, false);
                    $atoms = array();

                    break;

                case 'use-statement-end':
                    $useStatements[] = $this->useStatementFactory()->create(
                        $this->symbolFactory()->createFromAtoms($atoms, true),
                        $useStatementAlias,
                        $useStatementType
                    );
                    $atoms = array();
                    $useStatementAlias = $useStatementType = null;

                    break;

                case 'context-end':
                    if (
                        'end' !== $token[0] &&
                        null === $namespaceName &&
                        0 === count($useStatements) &&
                        0 === count($symbols)
                    ) {
                        break;
                    }

                    $context = $this->contextFactory()
                        ->create($namespaceName, $useStatements);
                    $namespaceName = null;
                    $useStatements = array();

                    foreach ($symbols as $index => $symbol) {
                        $symbols[$index] = $this->symbolResolver()
                            ->resolveAgainstContext($context, $symbol);
                    }

                    $contexts[] =
                        new ParsedResolutionContext($context, $symbols);
                    $symbols = array();

                    break;
            }

            $transition = null;
        }

        return $contexts;
    }

    private function normalizeTokens($tokens)
    {
        foreach ($tokens as $index => $token) {
            if (is_string($token)) {
                $tokens[$index] = array($token, $token, 0);
            }
        }

        return $tokens;
    }

    private static $instance;
    private $symbolFactory;
    private $symbolResolver;
    private $symbolNormalizer;
    private $useStatementFactory;
    private $contextFactory;
    private $traitTokenType;
}
