<?php

namespace PhpMiddlewareTest\PhpDebugBar;

use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware;
use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddlewareFactory;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Expressive\Application;
use Zend\Expressive\Router\FastRouteRouter;
use Zend\ServiceManager\ServiceManager;

final class ZendExpressiveTest extends AbstractMiddlewareRunnerTest
{
    protected function dispatchApplication(array $server, array $pipe = [])
    {
        $container = new ServiceManager([
            'factories' => [
                PhpDebugBarMiddleware::class => PhpDebugBarMiddlewareFactory::class,
            ],
        ]);
        $router    = new FastRouteRouter();
        $emitter = new TestEmitter();

        $app = new Application($router, $container, null, $emitter);

        $app->pipe(PhpDebugBarMiddleware::class);

        foreach ($pipe as $pattern => $middleware) {
            $app->get($pattern, $middleware);
        }

        $app->pipeRoutingMiddleware();
        $app->pipeDispatchMiddleware();

        $serverRequest = ServerRequestFactory::fromGlobals($server);

        $app->run($serverRequest);

        return $emitter->getResponse();
    }
}
