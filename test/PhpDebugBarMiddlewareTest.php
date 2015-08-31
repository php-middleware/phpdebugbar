<?php

namespace PhpMiddlewareTest\PhpDebugBar;

use DebugBar\JavascriptRenderer;
use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware;

/**
 * PhpDebugBarMiddlewareTest
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class PhpDebugBarMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    protected $debugbarRenderer;
    protected $middleware;

    protected function setUp()
    {
        $this->debugbarRenderer = $this->getMockBuilder(JavascriptRenderer::class)->disableOriginalConstructor()->getMock();
        $this->middleware = new PhpDebugBarMiddleware($this->debugbarRenderer);
    }

    public function testNotAttachIfNotAccept()
    {
        $request = new \Zend\Diactoros\ServerRequest([], [], null, null, 'php://input', ['Accept: application/json']);
        $response = new \Zend\Diactoros\Response();
        $calledOut = false;
        $outFunction = function ($request, $response) use (&$calledOut) {
            $calledOut = true;
            return $response;
        };

        $result = call_user_func($this->middleware, $request, $response, $outFunction);

        $this->assertTrue($calledOut, 'Out is not called');
        $this->assertSame($response, $result);
    }
}
