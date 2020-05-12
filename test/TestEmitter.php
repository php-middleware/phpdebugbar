<?php
declare (strict_types=1);

namespace PhpMiddlewareTest\PhpDebugBar;

use BadMethodCallException;
use Psr\Http\Message\ResponseInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

final class TestEmitter implements EmitterInterface
{
    private $response;

    public function emit(ResponseInterface $response): bool
    {
        $this->response = $response;

        return true;
    }

    public function getResponse(): ResponseInterface
    {
        if ($this->response instanceof ResponseInterface) {
            return $this->response;
        }

        throw new BadMethodCallException('Not emitted yet');
    }
}
