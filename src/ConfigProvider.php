<?php

namespace PhpMiddleware\PhpDebugBar;

final class ConfigProvider
{
    public static function getConfig()
    {
        $self = new self();
        return $self()();
    }

    public function __invoke()
    {
        $config = include __DIR__ . '/../config/phpdebugbar.config.php';
        $config['dependencies'] = include __DIR__ . '/../config/dependency.config.php';
        $config['middleware_pipeline'] = include __DIR__ . '/../config/zend-expressive.middleware_pipeline.config.php';

        return $config;
    }
}
