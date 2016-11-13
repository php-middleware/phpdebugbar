<?php

namespace PhpMiddlewareTest\PhpDebugBar;

use Interop\Container\ContainerInterface;
use PhpMiddleware\PhpDebugBar\ConfigProvider;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Expressive\Container\ApplicationFactory;
use Zend\ServiceManager\ServiceManager;

final class ZendExpressiveTest extends AbstractMiddlewareRunnerTest
{
    private $testEmitter;

    protected function setUp()
    {
        parent::setUp();

        $this->testEmitter = new TestEmitter();
    }

    protected function dispatchApplication(array $server, array $pipe = [])
    {
        $container = $this->createContainer();

        $appFactory = new ApplicationFactory();
        $app = $appFactory($container);

        foreach ($pipe as $pattern => $middleware) {
            $app->get($pattern, $middleware);
        }

        $app->pipeRoutingMiddleware();
        $app->pipeDispatchMiddleware();

        $serverRequest = ServerRequestFactory::fromGlobals($server);

        $app->run($serverRequest);

        return $this->testEmitter->getResponse();
    }

    /**
     *
     * @return ContainerInterface
     */
    private function createContainer()
    {
        $config = ConfigProvider::getConfig();

        $serviceManagerConfig = $config['dependencies'];
        $serviceManagerConfig['services']['config'] = $config;
        $serviceManagerConfig['services'][EmitterInterface::class] = $this->testEmitter;

        return new ServiceManager($serviceManagerConfig);
    }
}
