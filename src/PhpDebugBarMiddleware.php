<?php

namespace PhpMiddleware\PhpDebugBar;

use DebugBar\JavascriptRenderer as DebugBarRenderer;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\Serializer;

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
     * @param ResponseInterface $response
     * @param callable $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $outResponse = $next($request, $response);

        if (!$this->isHtmlAccepted($request)) {
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

        $outResponseBody = Serializer::toString($outResponse);
        $template = '<html><head>%s</head><body><h1>DebugBar</h1><p>Response:</p><pre>%s</pre>%s</body></html>';
        $escapedOutResponseBody = htmlspecialchars($outResponseBody);
        $result = sprintf($template, $debugBarHead, $escapedOutResponseBody, $debugBarBody);

        return new HtmlResponse($result);
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
