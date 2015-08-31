<?php

namespace PhpMiddlewareTest\PhpDebugBar;

use DebugBar\JavascriptRenderer;
use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * PhpDebugBarMiddlewareTest
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class PhpDebugBarMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    protected $debugbarRenderer;
    protected $middleware;

    protected function setUp()
    {
        $this->debugbarRenderer = $this->getMockBuilder(JavascriptRenderer::class)->disableOriginalConstructor()->getMock();
        $this->middleware = new PhpDebugBarMiddleware($this->debugbarRenderer);
    }

    public function testNotAttachIfNotAccept()
    {
        $request = new ServerRequest();
        $response = new Response();
        $calledOut = false;
        $outFunction = function ($request, $response) use (&$calledOut) {
            $calledOut = true;
            return $response;
        };

        $result = call_user_func($this->middleware, $request, $response, $outFunction);

        $this->assertTrue($calledOut, 'Out is not called');
        $this->assertSame($response, $result);
    }

    public function testAttachToNoneHtmlResponse()
    {
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html']);
        $response = new Response();
        $response->getBody()->write('ResponseBody');
        $calledOut = false;
        $outFunction = function ($request, $response) use (&$calledOut) {
            $calledOut = true;
            return $response;
        };

        $this->debugbarRenderer->expects($this->once())->method('renderHead')->willReturn('RenderHead');
        $this->debugbarRenderer->expects($this->once())->method('render')->willReturn('RenderBody');

        $result = call_user_func($this->middleware, $request, $response, $outFunction);

        $this->assertTrue($calledOut, 'Out is not called');
        $this->assertNotSame($response, $result);
        $this->assertSame("<html><head>RenderHead</head><body><h1>DebugBar</h1><p>Response:</p><pre>HTTP/1.1 200 OK\r\n\r\nResponseBody</pre>RenderBody</body></html>", (string) $result->getBody());
    }

    public function testAttachToHtmlResponse()
    {
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html']);
        $response = new Response('php://memory', 200, ['Content-Type' => 'text/html']);
        $response->getBody()->write('ResponseBody');
        $calledOut = false;
        $outFunction = function ($request, $response) use (&$calledOut) {
            $calledOut = true;
            return $response;
        };

        $this->debugbarRenderer->expects($this->once())->method('renderHead')->willReturn('RenderHead');
        $this->debugbarRenderer->expects($this->once())->method('render')->willReturn('RenderBody');

        $result = call_user_func($this->middleware, $request, $response, $outFunction);

        $this->assertTrue($calledOut, 'Out is not called');
        $this->assertSame($response, $result);
        $this->assertSame("ResponseBodyRenderHeadRenderBody", (string) $result->getBody());
    }
}
