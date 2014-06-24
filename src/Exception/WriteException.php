<?php

/*
 * This file is part of the Cosmos package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Cosmos\Exception;

use Eloquent\Pathogen\FileSystem\FileSystemPathInterface;
use Exception;

/**
 * Unable to write to a file or stream.
 */
final class WriteException extends Exception implements IoExceptionInterface
{
    /**
     * Construct a new write exception.
     *
     * @param FileSystemPathInterface|null $path  The path, if known.
     * @param Exception|null               $cause The cause, if available.
     */
    public function __construct(
        FileSystemPathInterface $path = null,
        Exception $cause = null
    ) {
        $this->path = $path;

        if (null === $path) {
            $message = 'Unable to write to stream.';
        } else {
            $message = sprintf(
                'Unable to write to %s',
                var_export($path->string(), true)
            );
        }

        if (null !== $cause) {
            $message .= ': ' . $cause->getMessage();
        }

        parent::__construct($message, 0, $cause);
    }

    /**
     * Get the path.
     *
     * @return FileSystemPathInterface|null The path, if known.
     */
    public function path()
    {
        return $this->path;
    }

    private $path;
}
