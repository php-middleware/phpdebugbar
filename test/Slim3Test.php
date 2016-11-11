<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace PhpMiddlewareTest\PhpDebugBar;

use DebugBar\StandardDebugBar;
use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware;
use PHPUnit_Framework_TestCase;
use Slim\App;

/**
 * Description of Slim3Test
 *
 * @author witold
 */
class Slim3Test extends PHPUnit_Framework_TestCase {

    public function testSlim3() {
        $app = new App();
        $app->getContainer()['enviroment'] = function() {
            $items = array(
                'DOCUMENT_ROOT' => '/home/witold/projects/slim3/public',
                'REMOTE_ADDR' => '127.0.0.1',
                'REMOTE_PORT' => '59638',
                'SERVER_SOFTWARE' => 'PHP 7.0.4-7ubuntu2.1 Development Server',
                'SERVER_PROTOCOL' => 'HTTP/1.1',
                'SERVER_NAME' => '0.0.0.0',
                'SERVER_PORT' => '8080',
                'REQUEST_URI' => '/phpdebugbar/debugbar.js',
                'REQUEST_METHOD' => 'GET',
                'SCRIPT_NAME' => '/phpdebugbar/debugbar.js',
                'SCRIPT_FILENAME' => '/home/witold/projects/slim3/public/public/index.php',
                'PHP_SELF' => '/phpdebugbar/debugbar.js',
                'HTTP_HOST' => '0.0.0.0:8080',
                'HTTP_CONNECTION' => 'keep-alive',
                'HTTP_CACHE_CONTROL' => 'max-age=0',
                'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.106 Safari/537.36',
                'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, sdch',
                'HTTP_ACCEPT_LANGUAGE' => 'pl-PL,pl;q=0.8,en-US;q=0.6,en;q=0.4',
                'HTTP_COOKIE' => 'zdt-hidden=0; PHPSESSID=tfun32lfu86islmbfk68s9eqi4',
                'REQUEST_TIME_FLOAT' => 1469139685.1076889,
                'REQUEST_TIME' => 1469139685,
            );
            return new \Slim\Http\Environment($items);
        };

        $debugbar = new StandardDebugBar();
        $debugbarRenderer = $debugbar->getJavascriptRenderer('/phpdebugbar');
        $middleware = new PhpDebugBarMiddleware($debugbarRenderer);
        $app->add($middleware);

        $app->get('/', function ($request, $response, $args) {
            $response->getBody()->write(' Hello ');

            return $response;
        });

        $response = $app->run(true);

        $this->assertContains('phpdebugbar', (string) $response->getBody());
    }

}
