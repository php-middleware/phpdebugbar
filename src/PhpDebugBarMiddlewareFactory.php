<?php
declare (strict_types=1);

namespace PhpMiddleware\PhpDebugBar;

use DebugBar\JavascriptRenderer;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PhpDebugBarMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): PhpDebugBarMiddleware
    {
        $renderer = $container->get(JavascriptRenderer::class);
        $responseFactory = $container->get(ResponseFactoryInterface::class);
        $streamFactory = $container->get(StreamFactoryInterface::class);

        return new PhpDebugBarMiddleware($renderer, $responseFactory, $streamFactory);
    }
}
