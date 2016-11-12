<?php

namespace PhpMiddlewareTest\PhpDebugBar;

use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddlewareFactory;
use Slim\App;
use Slim\Http\Environment;

final class Slim3Test extends AbstractMiddlewareRunnerTest
{
    protected function dispatchApplication(array $server, array $pipe = [])
    {
        $app = new App();
        $app->getContainer()['environment'] = function() use ($server) {
            return new Environment($server);
        };

        $middlewareFactory = new PhpDebugBarMiddlewareFactory();
        $middleware = $middlewareFactory();

        $app->add($middleware);

        foreach ($pipe as $pattern => $middleware) {
            $app->get($pattern, $middleware);
        }

        return $app->run(true);
    }
}
