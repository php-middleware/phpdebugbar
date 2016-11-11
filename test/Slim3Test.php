<?php

namespace PhpMiddlewareTest\PhpDebugBar;

use DebugBar\StandardDebugBar;
use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Http\Environment;

class Slim3Test extends PHPUnit_Framework_TestCase
{
    public function testAppendJsIntoHtmlContent()
    {
        $response = $this->dispatchApplication([
            'REQUEST_URI' => '/hello',
            'REQUEST_METHOD' => 'GET',
            'HTTP_ACCEPT' => 'text/html',
        ]);

        $responseBody = (string) $response->getBody();

        $this->assertContains('var phpdebugbar = new PhpDebugBar.DebugBar();', $responseBody);
        $this->assertContains('Hello!', $responseBody);
    }

    public function testGetStatics()
    {
        $response = $this->dispatchApplication([
            'REQUEST_URI' => '/phpdebugbar/debugbar.js',
            'REQUEST_METHOD' => 'GET',
            'HTTP_ACCEPT' => 'text/html',
        ]);

        $contentType = $response->getHeaderLine('Content-type');

        $this->assertContains('text/javascript', $contentType);
    }

    /**
     * @param array $server
     * @return ResponseInterface
     */
    protected function dispatchApplication(array $server)
    {
        $app = new App();
        $app->getContainer()['environment'] = function() use ($server) {
            $server['SCRIPT_NAME'] = '/index.php';
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
