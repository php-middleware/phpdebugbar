<?php

namespace PhpMiddlewareTest\PhpDebugBar;

use DebugBar\StandardDebugBar;
use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware;
use Slim\App;
use Slim\Http\Environment;

final class Slim3Test extends AbstractMiddlewareRunnerTest
{
    protected function dispatchApplication(array $server)
    {
        $app = new App();
        $app->getContainer()['environment'] = function() use ($server) {
            return new Environment($server);
        };

        $debugbar = new StandardDebugBar();
        $debugbarRenderer = $debugbar->getJavascriptRenderer('/phpdebugbar');
        $middleware = new PhpDebugBarMiddleware($debugbarRenderer);
        $app->add($middleware);

        $app->get('/hello', function ($request, $response, $args) {
            $response->getBody()->write('Hello!');

            return $response;
        });

        return $app->run(true);
    }
}
