<?php

/*
 * This file is part of the Cosmos package.
 *
 * Copyright © 2013 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eloquent\Cosmos\Exception;

use Exception;
use LogicException;

final class InvalidClassNameAtomException extends LogicException
{
    /**
     * @param string         $atom
     * @param Exception|null $previous
     */
    public function __construct($atom, Exception $previous = null)
    {
        $this->atom = $atom;

        parent::__construct(
            sprintf("Invalid class name atom '%s'.", $atom),
            0,
            $previous
        );
    }

    /**
     * @return string
     */
    public function atom()
    {
        return $this->atom;
    }

    private $atom;
}
