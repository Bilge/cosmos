<?php

/*
 * This file is part of the Cosmos package.
 *
 * Copyright © 2014 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Cosmos\Resolution\Context\Renderer;

use Eloquent\Cosmos\Resolution\Context\ResolutionContextInterface;

/**
 * The interface implemented by class name resolution context renderers.
 */
interface ResolutionContextRendererInterface
{
    /**
     * Render a class name resolution context.
     *
     * @param ResolutionContextInterface $context The context to render.
     *
     * @return string The rendered context.
     */
    public function renderContext(ResolutionContextInterface $context);
}
