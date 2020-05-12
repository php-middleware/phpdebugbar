<?php
declare (strict_types=1);

namespace PhpMiddlewareTest\PhpDebugBar;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response;

abstract class AbstractMiddlewareRunnerTest extends TestCase
{

    final public function testAppendJsIntoHtmlContent(): void
    {
        $response = $this->dispatchApplication([
            'REQUEST_URI' => '/hello',
            'REQUEST_METHOD' => 'GET',
            'HTTP_ACCEPT' => 'text/html',
        ], [
            '/hello' => function (ServerRequestInterface $request) {
                $response = new Response();
                $response->getBody()->write('Hello!');
                return $response;
            },
        ]);

        $responseBody = (string) $response->getBody();

        $this->assertStringContainsString('var phpdebugbar = new PhpDebugBar.DebugBar();', $responseBody);
        $this->assertStringContainsString('Hello!', $responseBody);
        $this->assertStringContainsString('"/phpdebugbar/debugbar.js"', $responseBody);
    }

    final public function testGetStatics(): void
    {
        $response = $this->dispatchApplication([
            'DOCUMENT_ROOT' => __DIR__,
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => '40226',
            'SERVER_SOFTWARE' => 'PHP 7.0.8-3ubuntu3 Development Server',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_NAME' => '0.0.0.0',
            'SERVER_PORT' => '8080',
            'REQUEST_URI' => '/phpdebugbar/debugbar.js',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/phpdebugbar/debugbar.js',
            'SCRIPT_FILENAME' => __FILE__,
            'PHP_SELF' => '/phpdebugbar/debugbar.js',
            'HTTP_HOST' => '0.0.0.0:8080',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        ]);

        $contentType = $response->getHeaderLine('Content-type');

        $this->assertStringContainsString('text/javascript', $contentType);
    }

    abstract protected function dispatchApplication(array $server, array $pipe = []): ResponseInterface;
}
