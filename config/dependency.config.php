<?php

return [
    'factories' => [
        \PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware::class => \PhpMiddleware\PhpDebugBar\PhpDebugBarMiddlewareFactory::class,
        \DebugBar\DataCollector\ConfigCollector::class => \PhpMiddleware\PhpDebugBar\ConfigCollectorFactory::class,
        \PhpMiddleware\PhpDebugBar\ConfigProvider::class => \PhpMiddleware\PhpDebugBar\ConfigProvider::class,
        \DebugBar\DebugBar::class => \PhpMiddleware\PhpDebugBar\StandardDebugBarFactory::class,
        \DebugBar\JavascriptRenderer::class => \PhpMiddleware\PhpDebugBar\JavascriptRendererFactory::class,
    ],
];
