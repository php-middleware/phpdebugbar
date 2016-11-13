<?php

namespace PhpMiddleware\PhpDebugBar\Exception;

use DomainException;
use Interop\Container\Exception\ContainerException;

class InvalidConfigException extends DomainException implements
    ContainerException,
    ExceptionInterface
{
}
