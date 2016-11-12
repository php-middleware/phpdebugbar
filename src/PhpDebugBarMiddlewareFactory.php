<?php

namespace PhpMiddleware\PhpDebugBar;

use DebugBar\StandardDebugBar;

/**
 * Default, simple factory for middleware
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
final class PhpDebugBarMiddlewareFactory
{
    public function __invoke()
    {
        $debugbar = new StandardDebugBar();
        $renderer = $debugbar->getJavascriptRenderer('/phpdebugbar');

        return new PhpDebugBarMiddleware($renderer);
    }
}
