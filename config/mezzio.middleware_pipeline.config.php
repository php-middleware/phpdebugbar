<?php

use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware;

return [
    PhpDebugBarMiddleware::class => [
        'middleware' => [
            PhpDebugBarMiddleware::class,
        ],
        'priority' => 1000,
    ],
];
