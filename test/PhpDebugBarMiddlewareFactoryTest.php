<?php

namespace PhpMiddlewareTest\PhpDebugBar;

use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware;
use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddlewareFactory;
use PHPUnit_Framework_TestCase;

/**
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class PhpDebugBarMiddlewareFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testFactory()
    {
        $factory = new PhpDebugBarMiddlewareFactory();

        $result = $factory();

        $this->assertInstanceOf(PhpDebugBarMiddleware::class, $result);
    }
}
