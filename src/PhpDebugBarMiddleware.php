<?php
declare (strict_types=1);

namespace PhpMiddleware\PhpDebugBar;

use DebugBar\JavascriptRenderer as DebugBarRenderer;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Http\Uri;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\Serializer;
use Zend\Diactoros\Stream;

/**
 * PhpDebugBarMiddleware
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
final class PhpDebugBarMiddleware implements MiddlewareInterface
{
    const FORCE_KEY = 'X-Debug-Bar';
    const FORCE_HEADER = self::FORCE_KEY;
    const FORCE_COOKIE = self::FORCE_KEY;
    const FORCE_ATTRIBUTE = self::FORCE_KEY;

    protected $debugBarRenderer;

    public function __construct(DebugBarRenderer $debugbarRenderer)
    {
        $this->debugBarRenderer = $debugbarRenderer;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($staticFile = $this->getStaticFile($request->getUri())) {
            return $staticFile;
        }

        $response = $handler->handle($request);

        $forceHeaderValue = $request->getHeaderLine(self::FORCE_HEADER);
        $forceCookieValue = $request->getCookieParams()[self::FORCE_COOKIE] ?? '';
        $forceAttibuteValue = $request->getAttribute(self::FORCE_ATTRIBUTE, '');
        $isForceEnable = in_array('true', [$forceHeaderValue, $forceCookieValue, $forceAttibuteValue], true);
        $isForceDisable = in_array('false', [$forceHeaderValue, $forceCookieValue, $forceAttibuteValue], true);

        if ($isForceDisable || (!$isForceEnable && !$this->isHtmlAccepted($request))) {
            return $response;
        }

        if ($this->isHtmlResponse($response)) {
            return $this->attachDebugBarToResponse($response);
        }
        return $this->prepareHtmlResponseWithDebugBar($response);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $handler = new class($next, $response) implements RequestHandlerInterface {
            private $next;
            private $response;

            public function __construct(callable $next, ResponseInterface $response)
            {
                $this->next = $next;
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->next)($request, $this->response);
            }
        };
        return $this->process($request, $handler);
    }

    /**
     * @return HtmlResponse
     */
    private function prepareHtmlResponseWithDebugBar(ResponseInterface $response)
    {
        $head = $this->debugBarRenderer->renderHead();
        $body = $this->debugBarRenderer->render();
        $outResponseBody = Serializer::toString($response);
        $template = '<html><head>%s</head><body><h1>DebugBar</h1><p>Response:</p><pre>%s</pre>%s</body></html>';
        $escapedOutResponseBody = htmlspecialchars($outResponseBody);
        $result = sprintf($template, $head, $escapedOutResponseBody, $body);

        return new HtmlResponse($result);
    }

    /**
     * @return ResponseInterface
     */
    private function attachDebugBarToResponse(ResponseInterface $response)
    {
        $head = $this->debugBarRenderer->renderHead();
        $body = $this->debugBarRenderer->render();
        $responseBody = $response->getBody();

        if (! $responseBody->eof() && $responseBody->isSeekable()) {
            $responseBody->seek(0, SEEK_END);
        }
        $responseBody->write($head . $body);

        return $response;
    }

    /**
     * @return ResponseInterface|null
     */
    private function getStaticFile(UriInterface $uri)
    {
        $path = $this->extractPath($uri);

        if (strpos($path, $this->debugBarRenderer->getBaseUrl()) !== 0) {
            return;
        }

        $pathToFile = substr($path, strlen($this->debugBarRenderer->getBaseUrl()));

        $fullPathToFile = $this->debugBarRenderer->getBasePath() . $pathToFile;

        if (!file_exists($fullPathToFile)) {
            return;
        }

        $contentType = $this->getContentTypeByFileName($fullPathToFile);
        $stream = new Stream($fullPathToFile, 'r');

        return new Response($stream, 200, [
            'Content-type' => $contentType,
        ]);
    }

    /**
     * @param UriInterface $uri
     *
     * @return string
     */
    private function extractPath(UriInterface $uri)
    {
        // Slim3 compatibility
        if ($uri instanceof Uri) {
            $basePath = $uri->getBasePath();
            if (!empty($basePath)) {
                return $basePath;
            }
        }
        return $uri->getPath();
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    private function getContentTypeByFileName($filename)
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        $map = [
            'css' => 'text/css',
            'js' => 'text/javascript',
            'otf' => 'font/opentype',
            'eot' => 'application/vnd.ms-fontobject',
            'svg' => 'image/svg+xml',
            'ttf' => 'application/font-sfnt',
            'woff' => 'application/font-woff',
            'woff2' => 'application/font-woff2',
        ];

        return isset($map[$ext]) ? $map[$ext] : 'text/plain';
    }

    /**
     * @param ResponseInterface $response
     *
     * @return bool
     */
    private function isHtmlResponse(ResponseInterface $response)
    {
        return $this->hasHeaderContains($response, 'Content-Type', 'text/html');
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function isHtmlAccepted(ServerRequestInterface $request)
    {
        return $this->hasHeaderContains($request, 'Accept', 'text/html');
    }

    /**
     * @param MessageInterface $message
     * @param string $headerName
     * @param string $value
     *
     * @return bool
     */
    private function hasHeaderContains(MessageInterface $message, $headerName, $value)
    {
        return strpos($message->getHeaderLine($headerName), $value) !== false;
    }
}
