<?php
declare (strict_types=1);

namespace PhpMiddleware\PhpDebugBar;

use DebugBar\StandardDebugBar;
use Psr\Container\ContainerInterface;

final class StandardDebugBarFactory
{
    public function __invoke(ContainerInterface $container): StandardDebugBar
    {
        $debugBar = new StandardDebugBar();

        $config = $container->get(ConfigProvider::class);

        $collectors = $config['phpmiddleware']['phpdebugbar']['collectors'];

        foreach ($collectors as $collectorName) {
            $collector = $container->get($collectorName);
            $debugBar->addCollector($collector);
        }

        $storage = $config['phpmiddleware']['phpdebugbar']['storage'];

        if (is_string($storage)) {
            $debugBar->setStorage(
                $container->get($storage)
            );
        }

        return $debugBar;
    }
}
