<?php
declare (strict_types=1);

namespace PhpMiddleware\PhpDebugBar;

use DebugBar\DebugBar;
use DebugBar\JavascriptRenderer;
use Psr\Container\ContainerInterface;

final class JavascriptRendererFactory
{
    public function __invoke(ContainerInterface $container = null): JavascriptRenderer
    {
        if ($container === null || !$container->has(DebugBar::class)) {
            $standardDebugBarFactory = new StandardDebugBarFactory();
            $debugbar = $standardDebugBarFactory($container);
        } else {
            $debugbar = $container->get(DebugBar::class);
        }

        $renderer = new JavascriptRenderer($debugbar);

        $config = $container !== null && $container->has('config') ? $container->get('config') : [];

        if (isset($config['phpmiddleware']['phpdebugbar']['javascript_renderer'])) {
            $rendererOptions = $config['phpmiddleware']['phpdebugbar']['javascript_renderer'];
        } else {
            $rendererOptions = [
                'base_url' => '/phpdebugbar',
            ];
        }

        $renderer->setOptions($rendererOptions);

        return $renderer;
    }
}
