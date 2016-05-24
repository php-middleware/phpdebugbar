<?php

namespace PhpMiddleware\PhpDebugBar;

use DebugBar\JavascriptRenderer as DebugBarRenderer;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\Serializer;
use Zend\Diactoros\Stream;

/**
 * PhpDebugBarMiddleware
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class PhpDebugBarMiddleware
{
    /**
     * @var DebugBarRenderer
     */
    protected $debugBarRenderer;

    /**
     * @param DebugBarRenderer $debugbarRenderer
     */
    public function __construct(DebugBarRenderer $debugbarRenderer)
    {
        $this->debugBarRenderer = $debugbarRenderer;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($staticFile = $this->getStaticFile($request->getUri())) {
            return $staticFile;
        }

        $outResponse = $next($request, $response);

        if (!$this->isHtmlAccepted($request) || $this->isRedirect($outResponse)) {
            return $outResponse;
        }

        $debugBarHead = $this->debugBarRenderer->renderHead();
        $debugBarBody = $this->debugBarRenderer->render();

        if ($this->isHtmlResponse($outResponse)) {
            $body = $outResponse->getBody();
            if (! $body->eof() && $body->isSeekable()) {
                $body->seek(0, SEEK_END);
            }
            $body->write($debugBarHead . $debugBarBody);

            return $outResponse;
        }

        $outResponseBody        = Serializer::toString($outResponse);
        $template               = '<html><head>%s</head><body><h1>DebugBar</h1><p>Response:</p><pre>%s</pre>%s</body></html>';
        $escapedOutResponseBody = htmlspecialchars($outResponseBody);
        $result                 = sprintf($template, $debugBarHead, $escapedOutResponseBody, $debugBarBody);

        return new HtmlResponse($result);
    }

    /**
     * @param UriInterface $uri
     *
     * @return ResponseInterface|null
     */
    private function getStaticFile(UriInterface $uri)
    {
        if (strpos($uri->getPath(), $this->debugBarRenderer->getBaseUrl()) !== 0) {
            return;
        }

        $pathToFile = substr($uri->getPath(), strlen($this->debugBarRenderer->getBaseUrl()));

        $fullPathToFile = $this->debugBarRenderer->getBasePath() . $pathToFile;

        if (!file_exists($fullPathToFile)) {
            return;
        }

        $stream         = new Stream($fullPathToFile, 'r');
        $staticResponse = new Response($stream);
        $contentType    = $this->getContentTypeByFileName($fullPathToFile);

        return $staticResponse->withHeader('Content-type', $contentType);
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
            'css'   => 'text/css',
            'js'    => 'text/javascript',
            'otf'   => 'font/opentype',
            'eot'   => 'application/vnd.ms-fontobject',
            'svg'   => 'image/svg+xml',
            'ttf'   => 'application/font-sfnt',
            'woff'  => 'application/font-woff',
            'woff2' => 'application/font-woff2',
        ];

        if (isset($map[$ext])) {
            return $map[$ext];
        }

        return 'text/plain';
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
     * @param string           $headerName
     * @param string           $value
     *
     * @return bool
     */
    private function hasHeaderContains(MessageInterface $message, $headerName, $value)
    {
        return strpos($message->getHeaderLine($headerName), $value) !== false;
    }

    /**
     * Returns a boolean TRUE for if the response has redirect status code.
     * 
     * Five common HTTP status codes indicates a redirection beginning from 301.
     * 304 not modified and 305 use proxy are not redirects.
     * 
     * @see https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection
     * 
     * @param MessageInterface $message
     *
     * @return bool
     */
    private function isRedirect(ResponseInterface $response)
    {
        return in_array($response->getStatusCode(), [301, 302, 303, 307, 308]);
    }
}
