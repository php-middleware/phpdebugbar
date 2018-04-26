<?php
declare (strict_types=1);

namespace PhpMiddleware\PhpDebugBar;

use DebugBar\JavascriptRenderer as DebugBarRenderer;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Http\Uri as SlimUri;
use Zend\Diactoros\Response as DiactorosResponse;
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
    public const FORCE_KEY = 'X-Enable-Debug-Bar';

    /**
     * @var DebugBarRenderer
     */
    protected $debugBarRenderer;

    /**
     * @var bool
     */
    protected $excludeNonHtmlContent;

    /**
     * @param DebugBarRenderer $debugbarRenderer
     * @param bool $excludeNonHtmlContent Whether to disable debugbar on content-types != 'test/html'
     */
    public function __construct(DebugBarRenderer $debugbarRenderer, $excludeNonHtmlContent=false)
    {
        $this->debugBarRenderer = $debugbarRenderer;
        $this->excludeNonHtmlContent = $excludeNonHtmlContent;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequest $request, RequestHandler $handler): Response
    {
        if ($staticFile = $this->getStaticFile($request->getUri())) {
            return $staticFile;
        }

        $response = $handler->handle($request);

        $forceHeaderValue = $request->getHeaderLine(self::FORCE_KEY);
        $forceCookieValue = $request->getCookieParams()[self::FORCE_KEY] ?? '';
        $forceAttibuteValue = $request->getAttribute(self::FORCE_KEY, '');
        $isForceEnable = in_array('true', [$forceHeaderValue, $forceCookieValue, $forceAttibuteValue], true);
        $isForceDisable = in_array('false', [$forceHeaderValue, $forceCookieValue, $forceAttibuteValue], true);

        if ($isForceDisable || (!$isForceEnable && ($this->isRedirect($response) || !$this->isHtmlAccepted($request)))) {
            return $response;
        }

        if ($this->isHtmlResponse($response)) {
            return $this->attachDebugBarToResponse($response);
        } elseif ($this->excludeNonHtmlContent) {
            return $response;
        }

        return $this->prepareHtmlResponseWithDebugBar($response);
    }

    public function __invoke(ServerRequest $request, Response $response, callable $next): Response
    {
        $handler = new class($next, $response) implements RequestHandler {
            private $next;
            private $response;

            public function __construct(callable $next, Response $response)
            {
                $this->next = $next;
                $this->response = $response;
            }

            public function handle(ServerRequest $request): Response
            {
                return ($this->next)($request, $this->response);
            }
        };
        return $this->process($request, $handler);
    }

    private function prepareHtmlResponseWithDebugBar(Response $response): HtmlResponse
    {
        $head = $this->debugBarRenderer->renderHead();
        $body = $this->debugBarRenderer->render();
        $outResponseBody = Serializer::toString($response);
        $template = '<html><head>%s</head><body><h1>DebugBar</h1><p>Response:</p><pre>%s</pre>%s</body></html>';
        $escapedOutResponseBody = htmlspecialchars($outResponseBody);
        $result = sprintf($template, $head, $escapedOutResponseBody, $body);

        return new HtmlResponse($result);
    }

    private function attachDebugBarToResponse(Response $response): Response
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

    private function getStaticFile(UriInterface $uri): ?Response
    {
        $path = $this->extractPath($uri);

        if (strpos($path, $this->debugBarRenderer->getBaseUrl()) !== 0) {
            return null;
        }

        $pathToFile = substr($path, strlen($this->debugBarRenderer->getBaseUrl()));

        $fullPathToFile = $this->debugBarRenderer->getBasePath() . $pathToFile;

        if (!file_exists($fullPathToFile)) {
            return null;
        }

        $contentType = $this->getContentTypeByFileName($fullPathToFile);
        $stream = new Stream($fullPathToFile, 'r');

        return new DiactorosResponse($stream, 200, [
            'Content-type' => $contentType,
        ]);
    }

    private function extractPath(UriInterface $uri): string
    {
        // Slim3 compatibility
        if ($uri instanceof SlimUri) {
            $basePath = $uri->getBasePath();
            if (!empty($basePath)) {
                return $basePath;
            }
        }
        return $uri->getPath();
    }

    private function getContentTypeByFileName(string $filename): string
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

    private function isHtmlResponse(Response $response): bool
    {
        return $this->hasHeaderContains($response, 'Content-Type', 'text/html');
    }

    private function isHtmlAccepted(ServerRequest $request): bool
    {
        return $this->hasHeaderContains($request, 'Accept', 'text/html');
    }

    private function hasHeaderContains(MessageInterface $message, string $headerName, string $value): bool
    {
        return strpos($message->getHeaderLine($headerName), $value) !== false;
    }

    private function isRedirect(Response $response): bool
    {
        $statusCode = $response->getStatusCode();

        return ($statusCode >= 300 || $statusCode < 400) && $response->getHeaderLine('Location') !== '';
    }
}
