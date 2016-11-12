<?php

namespace PhpMiddlewareTest\PhpDebugBar;

use BadMethodCallException;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\EmitterInterface;

final class TestEmitter implements EmitterInterface
{
    private $response;

    public function emit(ResponseInterface $response)
    {
        $this->response = $response;

        return $response;
    }

    public function getResponse()
    {
        if ($this->response instanceof ResponseInterface) {
            return $this->response;
        }

        throw new BadMethodCallException('Not emitted yet');
    }
}
