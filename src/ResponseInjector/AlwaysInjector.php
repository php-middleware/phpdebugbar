<?php
declare (strict_types=1);

namespace PhpMiddleware\PhpDebugBar\ResponseInjector;

use DebugBar\JavascriptRenderer;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\Serializer;

final class AlwaysInjector implements ResponseInjectorInterface
{
    public function injectPhpDebugBar(ResponseInterface $response, JavascriptRenderer $debugBarRenderer): ResponseInterface
    {
        $debugBarHead = $debugBarRenderer->renderHead();
        $debugBarBody = $debugBarRenderer->render();

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

    private function isHtmlResponse(ResponseInterface $response): bool
    {
        return $this->hasHeaderContains($response, 'Content-Type', 'text/html');
    }
}
