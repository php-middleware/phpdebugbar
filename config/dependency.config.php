<?php

return [
    'factories' => [
        PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware::class => PhpMiddleware\PhpDebugBar\PhpDebugBarMiddlewareFactory::class,
        DebugBar\DataCollector\ConfigCollector::class => PhpMiddleware\PhpDebugBar\ConfigCollectorFactory::class,
    ],
];
