<?php

namespace PhpMiddleware\PhpDebugBar;

final class ConfigProvider
{
    public static function getConfig()
    {
        $self = new self();
        return $self();
    }

    public function __invoke()
    {
        return [
            'dependencies' => include __DIR__ . '/../config/dependency.config.php',
            'middleware_pipeline' => include __DIR__ . '/../config/zend-expressive.middleware_pipeline.config.php',
        ];
    }
}
