<?php

namespace PhpMiddleware\PhpDebugBar;

use DebugBar\JavascriptRenderer;

final class JavascriptRendererFactory
{
    public function __invoke()
    {
        $standardDebugBarFactory = new StandardDebugBarFactory();
        $debugbar = $standardDebugBarFactory();

        $renderer = new JavascriptRenderer($debugbar, '/phpdebugbar');

        return $renderer;
    }
}
