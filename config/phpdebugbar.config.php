<?php

return [
    'phpmiddleware' => [
        'phpdebugbar' => [
            'javascript_renderer' => [
                'base_url' => '/phpdebugbar',
            ],
            'collectors' => [
                DebugBar\DataCollector\ConfigCollector::class,
            ],
            'storage' => null,
        ],
    ],
];
