<?php
declare (strict_types=1);

namespace PhpMiddleware\PhpDebugBar\ResponseInjector;

use DebugBar\JavascriptRenderer;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
interface ResponseInjectorInterface
{
    public function injectPhpDebugBar(ResponseInterface $response, JavascriptRenderer $debugBar): ResponseInterface;
}
