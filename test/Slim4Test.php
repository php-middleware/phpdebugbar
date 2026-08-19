<?php
declare (strict_types=1);

namespace PhpMiddlewareTest\PhpDebugBar;

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\ServiceManager\ServiceManager;
use PhpMiddleware\PhpDebugBar\ConfigProvider;
use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Slim\Factory\AppFactory;

final class Slim4Test extends AbstractMiddlewareRunnerTest
{
    protected function dispatchApplication(array $server, array $pipe = []): ResponseInterface
    {
        $container = new ServiceManager();
        $container->setService(ResponseFactoryInterface::class, new ResponseFactory());
        $container->setService(StreamFactoryInterface::class, new StreamFactory());

        $config = ConfigProvider::getConfig();

        foreach ($config['dependencies']['factories'] as $name => $factory) {
            $container->setFactory($name, $factory);
        }

        $app = AppFactory::create(new ResponseFactory());

        $app->add($container->get(PhpDebugBarMiddleware::class));

        foreach ($pipe as $pattern => $handler) {
            $app->get($pattern, function (ServerRequestInterface $request) use ($handler): ResponseInterface {
                return $handler($request);
            });
        }

        return $app->handle(ServerRequestFactory::fromGlobals($server));
    }
}
