<?php
declare (strict_types=1);

namespace PhpMiddlewareTest\PhpDebugBar;

use PhpMiddleware\PhpDebugBar\ConfigProvider;
use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Slim\App;
use Slim\Http\Environment;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;

final class Slim3Test extends AbstractMiddlewareRunnerTest
{
    protected function dispatchApplication(array $server, array $pipe = []): ResponseInterface
    {
        $app = new App();
        $container = $app->getContainer();
        $container[ResponseFactoryInterface::class] = new ResponseFactory();
        $container[StreamFactoryInterface::class] = new StreamFactory();
        $container['environment'] = function() use ($server) {
            return new Environment($server);
        };

        $config = ConfigProvider::getConfig();

        foreach ($config['dependencies']['factories'] as $key => $factory) {
            $container[$key] = new $factory();
        }

        $middleware = $container->get(PhpDebugBarMiddleware::class);

        $app->add($middleware);

        foreach ($pipe as $pattern => $middleware) {
            $app->get($pattern, $middleware);
        }

        return $app->run(true);
    }
}
