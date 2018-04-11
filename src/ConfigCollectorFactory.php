<?php
declare (strict_types=1);

namespace PhpMiddleware\PhpDebugBar;

use DebugBar\DataCollector\ConfigCollector;
use Psr\Container\ContainerInterface;

final class ConfigCollectorFactory
{
    public function __invoke(ContainerInterface $container): ConfigCollector
    {
        $data = $container->get('config');

        return new ConfigCollector($data, 'Config');
    }
}
