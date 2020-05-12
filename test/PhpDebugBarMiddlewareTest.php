<?php
declare (strict_types=1);

namespace PhpMiddlewareTest\PhpDebugBar;

use DebugBar\JavascriptRenderer;
use org\bovigo\vfs\vfsStream;
use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware;
use PHPUnit\Framework\TestCase;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\Uri;

/**
 * PhpDebugBarMiddlewareTest
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class PhpDebugBarMiddlewareTest extends TestCase
{
    protected $debugbarRenderer;
    /** @var PhpDebugBarMiddleware */
    protected $middleware;

    protected function setUp(): void
    {
        $this->debugbarRenderer = $this->getMockBuilder(JavascriptRenderer::class)->disableOriginalConstructor()->getMock();
        $this->debugbarRenderer->method('renderHead')->willReturn('RenderHead');
        $this->debugbarRenderer->method('getBaseUrl')->willReturn('/phpdebugbar');
        $this->debugbarRenderer->method('render')->willReturn('RenderBody');
        $responseFactory = new ResponseFactory();
        $streamFactory = new StreamFactory();

        $this->middleware = new PhpDebugBarMiddleware($this->debugbarRenderer, $responseFactory, $streamFactory);
    }

    public function testTwoPassCallingForCompatibility(): void
    {
        $request = new ServerRequest();
        $response = new Response();
        $response->getBody()->write('ResponseBody');
        $calledOut = false;
        $outFunction = function ($request, $response) use (&$calledOut) {
            $calledOut = true;
            return $response;
        };

        $result = call_user_func($this->middleware, $request, $response, $outFunction);

        $this->assertTrue($calledOut, 'Out is not called');
        $this->assertSame('ResponseBody', (string) $result->getBody());
        $this->assertSame($response, $result);
    }

    public function testNotAttachIfNotAccept(): void
    {
        $request = new ServerRequest();
        $response = new Response();
        $response->getBody()->write('ResponseBody');
        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertTrue($requestHandler->isCalled(), 'Request handler is not called');
        $this->assertSame('ResponseBody', (string) $result->getBody());
        $this->assertSame($response, $result);
    }

    public function testForceAttachDebugbarIfHeaderPresents(): void
    {
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'application/json', 'X-Enable-Debug-Bar' => 'true']);
        $response = new Response();
        $response->getBody()->write('ResponseBody');
        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame("<html><head>RenderHead</head><body><h1>DebugBar</h1><p>Response:</p><pre>HTTP/1.1 200 OK\r\n\r\nResponseBody</pre>RenderBody</body></html>", (string) $result->getBody());
    }

    public function testForceAttachDebugbarIfCookiePresents(): void
    {
        $cookies = ['X-Enable-Debug-Bar' => 'true'];
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'application/json'], $cookies);
        $response = new Response();
        $response->getBody()->write('ResponseBody');
        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertSame("<html><head>RenderHead</head><body><h1>DebugBar</h1><p>Response:</p><pre>HTTP/1.1 200 OK\r\n\r\nResponseBody</pre>RenderBody</body></html>", (string) $result->getBody());
    }

    public function testForceAttachDebugbarIfAttributePresents(): void
    {
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'application/json']);
        $request = $request->withAttribute('X-Enable-Debug-Bar', 'true');
        $response = new Response();
        $response->getBody()->write('ResponseBody');
        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertSame("<html><head>RenderHead</head><body><h1>DebugBar</h1><p>Response:</p><pre>HTTP/1.1 200 OK\r\n\r\nResponseBody</pre>RenderBody</body></html>", (string) $result->getBody());
    }

    public function testAttachToNoneHtmlResponse(): void
    {
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html']);
        $response = (new Response())->withHeader('test-header', 'value');
        $response->getBody()->write('ResponseBody');

        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertTrue($requestHandler->isCalled(), 'Request handler is not called');
        $this->assertNotSame($response, $result);
        $this->assertSame("<html><head>RenderHead</head><body><h1>DebugBar</h1><p>Response:</p><pre>HTTP/1.1 200 OK\r\nTest-Header: value\r\n\r\nResponseBody</pre>RenderBody</body></html>", (string) $result->getBody());
    }

    public function testNotAttachToRedirectResponse(): void
    {
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html']);
        $response = (new Response())->withStatus(300)->withAddedHeader('Location', 'some-location');

        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertSame($response, $result);
    }

    public function testAttachToNonRedirectResponse(): void
    {
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html']);
        $response = (new Response())->withStatus(299)->withAddedHeader('Location', 'some-location');

        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertNotSame($response, $result);
    }

    public function testAttachToNonRedirectResponse2(): void
    {
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html']);
        $response = (new Response())->withStatus(400)->withAddedHeader('Location', 'some-location');

        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertNotSame($response, $result);
    }

    public function testAttachToRedirectResponseWithoutLocation(): void
    {
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html']);
        $response = (new Response())->withStatus(302);

        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertTrue($requestHandler->isCalled(), 'Request handler is not called');
        $this->assertNotSame($response, $result);
        $this->assertSame("<html><head>RenderHead</head><body><h1>DebugBar</h1><p>Response:</p><pre>HTTP/1.1 302 Found\r\n\r\n</pre>RenderBody</body></html>", (string) $result->getBody());
    }

    public function testForceAttachToRedirectResponse(): void
    {
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html', 'X-Enable-Debug-Bar' => 'true']);
        $response = (new Response())->withStatus(302)->withAddedHeader('Location', 'some-location');

        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertTrue($requestHandler->isCalled(), 'Request handler is not called');
        $this->assertNotSame($response, $result);
        $this->assertSame("<html><head>RenderHead</head><body><h1>DebugBar</h1><p>Response:</p><pre>HTTP/1.1 302 Found\r\nLocation: some-location\r\n\r\n</pre>RenderBody</body></html>", (string) $result->getBody());
    }

    public function testAttachToHtmlResponse(): void
    {
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html']);
        $response = new Response('php://memory', 200, ['Content-Type' => 'text/html']);
        $response->getBody()->write('ResponseBody');
        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertTrue($requestHandler->isCalled(), 'Request handler is not called');
        $this->assertSame($response, $result);
        $this->assertSame('ResponseBodyRenderHeadRenderBody', (string) $result->getBody());
    }

    public function testForceNotAttachDebugbarIfHeaderPresents(): void
    {
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html', 'X-Enable-Debug-Bar' => 'false']);
        $response = new Response('php://memory', 200, ['Content-Type' => 'text/html']);
        $response->getBody()->write('ResponseBody');
        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertTrue($requestHandler->isCalled(), 'Request handler is not called');
        $this->assertSame($response, $result);
        $this->assertSame('ResponseBody', (string) $result->getBody());
    }

    public function testForceNotAttachDebugbarIfCookiePresents(): void
    {
        $cookie = ['X-Enable-Debug-Bar' => 'false'];
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html'], $cookie);
        $response = new Response('php://memory', 200, ['Content-Type' => 'text/html']);
        $response->getBody()->write('ResponseBody');
        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertTrue($requestHandler->isCalled(), 'Request handler is not called');
        $this->assertSame($response, $result);
        $this->assertSame('ResponseBody', (string) $result->getBody());
    }

    public function testForceNotAttachDebugbarIfAttributePresents(): void
    {
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html']);
        $request = $request->withAttribute('X-Enable-Debug-Bar', 'false');
        $response = new Response('php://memory', 200, ['Content-Type' => 'text/html']);
        $response->getBody()->write('ResponseBody');
        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertTrue($requestHandler->isCalled(), 'Request handler is not called');
        $this->assertSame($response, $result);
        $this->assertSame('ResponseBody', (string) $result->getBody());
    }

    public function testAppendsToEndOfHtmlResponse(): void
    {
        $html = '<html><head><title>Foo</title></head><body>Content</body>';
        $request = new ServerRequest([], [], null, null, 'php://input', ['Accept' => 'text/html']);
        $response = new Response\HtmlResponse($html);
        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertTrue($requestHandler->isCalled(), 'Request handler is not called');
        $this->assertSame($response, $result);
        $this->assertSame($html . 'RenderHeadRenderBody', (string) $result->getBody());
    }

    public function testTryToHandleNotExistingStaticFile(): void
    {
        $uri = new Uri('http://example.com/phpdebugbar/boo.css');
        $request = new ServerRequest([], [], $uri, null, 'php://memory');
        $response = new Response\HtmlResponse('<html></html>');
        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertTrue($requestHandler->isCalled(), 'Request handler is not called');
        $this->assertSame($response, $result);
    }

    /**
     * @dataProvider getContentTypes
     */
    public function testHandleStaticFile(string $extension, string $contentType): void
    {
        $root = vfsStream::setup('boo');

        $this->debugbarRenderer->expects($this->any())->method('getBasePath')->willReturn(vfsStream::url('boo'));

        $uri = new Uri(sprintf('http://example.com/phpdebugbar/debugbar.%s', $extension));
        $request = new ServerRequest([], [], $uri, null, 'php://memory');
        $response = new Response\HtmlResponse('<html></html>');

        vfsStream::newFile(sprintf('debugbar.%s', $extension))->withContent('filecontent')->at($root);

        $requestHandler = new RequestHandlerStub($response);

        $result = $this->middleware->process($request, $requestHandler);

        $this->assertFalse($requestHandler->isCalled(), 'Request handler is called');
        $this->assertNotSame($response, $result);
        $this->assertSame($contentType, $result->getHeaderLine('Content-type'));
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('filecontent', (string) $result->getBody());
    }

    public function getContentTypes(): array
    {
        return [
            ['css', 'text/css'],
            ['js', 'text/javascript'],
            ['html', 'text/plain'],
        ];
    }
}
