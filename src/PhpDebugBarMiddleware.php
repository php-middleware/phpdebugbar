<?php
declare (strict_types=1);

namespace PhpMiddleware\PhpDebugBar;

use DebugBar\JavascriptRenderer as DebugBarRenderer;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Http\Uri as SlimUri;

/**
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
final class PhpDebugBarMiddleware implements MiddlewareInterface
{
    public const FORCE_KEY = 'X-Enable-Debug-Bar';

    /**
     * @var DebugBarRenderer
     */
    private $debugBarRenderer;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    public function __construct(
        DebugBarRenderer $debugBarRenderer,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->debugBarRenderer = $debugBarRenderer;
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
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

        if ($this->shouldReturnResponse($request, $response)) {
            return $response;
        }

        if ($this->isHtmlResponse($response)) {
            return $this->attachDebugBarToHtmlResponse($response);
        }

        return $this->prepareHtmlResponseWithDebugBar($response);
    }

    public function __invoke(ServerRequest $request, Response $response, callable $next): Response
    {
        $handler = new class($next, $response) implements RequestHandler {
            /**
             * @var callable
             */
            private $next;

            /**
             * @var Response
             */
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

    private function shouldReturnResponse(ServerRequest $request, Response $response): bool
    {
        $forceHeaderValue = $request->getHeaderLine(self::FORCE_KEY);
        $forceCookieValue = $request->getCookieParams()[self::FORCE_KEY] ?? '';
        $forceAttributeValue = $request->getAttribute(self::FORCE_KEY, '');
        $isForceEnable = in_array('true', [$forceHeaderValue, $forceCookieValue, $forceAttributeValue], true);
        $isForceDisable = in_array('false', [$forceHeaderValue, $forceCookieValue, $forceAttributeValue], true);

        return $isForceDisable || (!$isForceEnable && ($this->isRedirect($response) || !$this->isHtmlAccepted($request)));
    }

    private function prepareHtmlResponseWithDebugBar(Response $response): Response
    {
        $head = $this->debugBarRenderer->renderHead();
        $body = $this->debugBarRenderer->render();
        $outResponseBody = $this->serializeResponse($response);
        $template = '<html><head>%s</head><body><h1>DebugBar</h1><p>Response:</p><pre>%s</pre>%s</body></html>';
        $escapedOutResponseBody = htmlspecialchars($outResponseBody);
        $result = sprintf($template, $head, $escapedOutResponseBody, $body);

        $stream = $this->streamFactory->createStream($result);

        return $this->responseFactory->createResponse()
            ->withBody($stream)
            ->withAddedHeader('Content-type', 'text/html');
    }

    private function attachDebugBarToHtmlResponse(Response $response): Response
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
        $stream = $this->streamFactory->createStreamFromResource(fopen($fullPathToFile, 'rb'));

        return $this->responseFactory->createResponse()
            ->withBody($stream)
            ->withAddedHeader('Content-type', $contentType);
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

        return $map[$ext] ?? 'text/plain';
    }

    private function isHtmlResponse(Response $response): bool
    {
        return $this->isHtml($response, 'Content-Type');
    }

    private function isHtmlAccepted(ServerRequest $request): bool
    {
        return $this->isHtml($request, 'Accept');
    }

    private function isHtml(MessageInterface $message, string $headerName): bool
    {
        return strpos($message->getHeaderLine($headerName), 'text/html') !== false;
    }

    private function isRedirect(Response $response): bool
    {
        $statusCode = $response->getStatusCode();

        return $statusCode >= 300 && $statusCode < 400 && $response->getHeaderLine('Location') !== '';
    }

    private function serializeResponse(Response $response) : string
    {
        $reasonPhrase = $response->getReasonPhrase();
        $headers      = $this->serializeHeaders($response->getHeaders());
        $format       = 'HTTP/%s %d%s%s%s';

        if (! empty($headers)) {
            $headers = "\r\n" . $headers;
        }

        $headers .= "\r\n\r\n";

        return sprintf(
            $format,
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            ($reasonPhrase ? ' ' . $reasonPhrase : ''),
            $headers,
            $response->getBody()
        );
    }

    /**
     * @param array<string, array<string>> $headers
     */
    private function serializeHeaders(array $headers) : string
    {
        $lines = [];
        foreach ($headers as $header => $values) {
            $normalized = $this->filterHeader($header);
            foreach ($values as $value) {
                $lines[] = sprintf('%s: %s', $normalized, $value);
            }
        }

        return implode("\r\n", $lines);
    }

    private function filterHeader(string $header) : string
    {
        $filtered = str_replace('-', ' ', $header);
        $filtered = ucwords($filtered);
        return str_replace(' ', '-', $filtered);
    }
}
