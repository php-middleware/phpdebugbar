<?php

namespace PhpMiddleware\PhpDebugBar;

/**
 * Default, simple factory for middleware
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
final class PhpDebugBarMiddlewareFactory
{
    public function __invoke()
    {
        $rendererFactory = new JavascriptRendererFactory();
        $renderer = $rendererFactory();

        return new PhpDebugBarMiddleware($renderer);
    }
}
