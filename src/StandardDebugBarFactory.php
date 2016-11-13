<?php

namespace PhpMiddleware\PhpDebugBar;

use DebugBar\StandardDebugBar;

final class StandardDebugBarFactory
{
    public function __invoke()
    {
        return new StandardDebugBar();
    }
}
