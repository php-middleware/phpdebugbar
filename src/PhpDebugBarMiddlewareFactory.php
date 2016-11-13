<?php

namespace PhpMiddleware\PhpDebugBar;

use DebugBar\JavascriptRenderer;
use Interop\Container\ContainerInterface;

final class PhpDebugBarMiddlewareFactory
{
    public function __invoke(ContainerInterface $container = null)
    {
        if ($container === null || !$container->has(JavascriptRenderer::class)) {
            $rendererFactory = new JavascriptRendererFactory();
            $renderer = $rendererFactory($container);
        } else {
            $renderer = $container->get(JavascriptRenderer::class);
        }
        return new PhpDebugBarMiddleware($renderer);
    }
}
