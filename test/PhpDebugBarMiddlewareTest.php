<?php

namespace PhpMiddlewareTest\PhpDebugBar;

use DebugBar\JavascriptRenderer;
use org\bovigo\vfs\vfsStream;
use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware;
use PHPUnit_Framework_TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

/**
 * PhpDebugBarMiddlewareTest
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class PhpDebugBarMiddlewareTest extends PHPUnit_Framework_TestCase
{
    protected $debugbarRenderer;
    protected $middleware;

    protected function setUp()
    {
        $this->debugbarRenderer = $this->getMockBuilder(JavascriptRenderer::class)->disableOriginalConstructor()->getMock();
        $this->middleware       = new PhpDebugBarMiddleware($this->debugbarRenderer);
    }

    public function testNotAttachIfNotAccept()
    {
        $request     = new ServerRequest();
        $response    = new Response();
        $calledOut   = false;
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
        $request  = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html']);
        $response = new Response();
        $response->getBody()->write('ResponseBody');
        $calledOut   = false;
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
        $request  = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html']);
        $response = new Response('php://memory', 200, ['Content-Type' => 'text/html']);
        $response->getBody()->write('ResponseBody');
        $calledOut   = false;
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

    public function testAppendsToEndOfHtmlResponse()
    {
        $html        = '<html><head><title>Foo</title></head><body>Content</body>';
        $request     = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html']);
        $response    = new Response\HtmlResponse($html);
        $calledOut   = false;
        $outFunction = function ($request, $response) use (&$calledOut) {
            $calledOut = true;

            return $response;
        };

        $this->debugbarRenderer->expects($this->once())->method('renderHead')->willReturn('RenderHead');
        $this->debugbarRenderer->expects($this->once())->method('render')->willReturn('RenderBody');

        $result = call_user_func($this->middleware, $request, $response, $outFunction);

        $this->assertTrue($calledOut, 'Out is not called');
        $this->assertSame($response, $result);
        $this->assertSame($html . 'RenderHeadRenderBody', (string) $result->getBody());
    }

    public function testTryToHandleNotExistingStaticFile()
    {
        $this->debugbarRenderer->expects($this->any())->method('getBaseUrl')->willReturn('/phpdebugbar');

        $uri      = new Uri('http://example.com/phpdebugbar/boo.css');
        $request  = new ServerRequest([], [], $uri, null, 'php://memory');
        $response = new Response\HtmlResponse('<html></html>');

        $calledOut   = false;
        $outFunction = function ($request, $response) use (&$calledOut) {
            $calledOut = true;

            return $response;
        };

        $result = call_user_func($this->middleware, $request, $response, $outFunction);
        $this->assertTrue($calledOut, 'Out is not called');
        $this->assertSame($response, $result);
    }

    /**
     * @dataProvider getContentTypes
     */
    public function testHandleStaticFile($extension, $contentType)
    {
        $root = vfsStream::setup('boo');

        $this->debugbarRenderer->expects($this->any())->method('getBaseUrl')->willReturn('/phpdebugbar');
        $this->debugbarRenderer->expects($this->any())->method('getBasePath')->willReturn(vfsStream::url('boo'));

        $uri      = new Uri(sprintf('http://example.com/phpdebugbar/debugbar.%s', $extension));
        $request  = new ServerRequest([], [], $uri, null, 'php://memory');
        $response = new Response\HtmlResponse('<html></html>');

        vfsStream::newFile(sprintf('debugbar.%s', $extension))->withContent('filecontent')->at($root);

        $calledOut   = false;
        $outFunction = function ($request, $response) use (&$calledOut) {
            $calledOut = true;

            return $response;
        };

        $result = call_user_func($this->middleware, $request, $response, $outFunction);
        $this->assertFalse($calledOut, 'Out is called');
        $this->assertNotSame($response, $result);
        $this->assertSame($contentType, $result->getHeaderLine('Content-type'));
        $this->assertSame('filecontent', (string) $result->getBody());
    }

    /**
     * @dataProvider statusCodeProvider
     */
    public function testHandleRedirects($code, $handle)
    {
        $request     = new ServerRequest();
        $response    = new Response('php://memory', $code, ['Location' => 'http://www.foo.bar']);
        $calledOut   = false;
        $outFunction = function ($request, $response) use (&$calledOut) {
            $calledOut = true;

            return $response;
        };

        $result = call_user_func($this->middleware, $request, $response, $outFunction);

        $this->assertEquals($response->getStatusCode() === $code, $handle);
        $this->assertTrue($calledOut, 'Out is not called');
        $this->assertSame($response, $result);
    }

    public function statusCodeProvider()
    {
        return [
            [301, true],
            [302, true],
            [303, true],
            [307, true],
            [308, true],
        ];
    }

    public function getContentTypes()
    {
        return [
            ['css', 'text/css'],
            ['js', 'text/javascript'],
            ['html', 'text/plain'],
        ];
    }
}
