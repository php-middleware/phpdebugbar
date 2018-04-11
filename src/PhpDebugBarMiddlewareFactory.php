<?php
declare (strict_types=1);

namespace PhpMiddleware\PhpDebugBar;

use DebugBar\JavascriptRenderer;
use Psr\Container\ContainerInterface;

final class PhpDebugBarMiddlewareFactory
{
    public function __invoke(ContainerInterface $container = null): PhpDebugBarMiddleware
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
