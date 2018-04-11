<?php
declare (strict_types=1);

namespace PhpMiddleware\PhpDebugBar;

use DebugBar\StandardDebugBar;
use Psr\Container\ContainerInterface;

final class StandardDebugBarFactory
{
    public function __invoke(ContainerInterface $container = null): StandardDebugBar
    {
        $debugBar = new StandardDebugBar();

        if ($container !== null) {
            $config = $container->has('config') ? $container->get('config') : [];

            $collectors = isset($config['phpmiddleware']['phpdebugbar']['collectors']) ? $config['phpmiddleware']['phpdebugbar']['collectors'] : [];

            foreach ($collectors as $collectorName) {
                $collector = $container->get($collectorName);
                $debugBar->addCollector($collector);
            }

            if (isset($config['phpmiddleware']['phpdebugbar']['storage']) && is_string($config['phpmiddleware']['phpdebugbar']['storage'])) {
                $storage = $container->get($config['phpmiddleware']['phpdebugbar']['storage']);
                $debugBar->setStorage($config['phpmiddleware']['phpdebugbar']['storage']);
            }
        }

        return $debugBar;
    }
}
