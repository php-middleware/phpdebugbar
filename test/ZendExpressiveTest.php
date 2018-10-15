<?php
declare (strict_types=1);

namespace PhpMiddlewareTest\PhpDebugBar;

use PhpMiddleware\PhpDebugBar\ConfigProvider;
use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ResponseFactory;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\StreamFactory;
use Zend\Expressive\Container\ApplicationFactory;
use Zend\Expressive\Container\MiddlewareContainerFactory;
use Zend\Expressive\Container\MiddlewareFactoryFactory;
use Zend\Expressive\Container\RequestHandlerRunnerFactory;
use Zend\Expressive\Container\ResponseFactoryFactory;
use Zend\Expressive\Container\ServerRequestErrorResponseGeneratorFactory;
use Zend\Expressive\MiddlewareContainer;
use Zend\Expressive\MiddlewareFactory;
use Zend\Expressive\Response\ServerRequestErrorResponseGenerator;
use Zend\Expressive\Router\FastRouteRouter;
use Zend\Expressive\Router\FastRouteRouterFactory;
use Zend\Expressive\Router\Middleware\DispatchMiddleware;
use Zend\Expressive\Router\Middleware\DispatchMiddlewareFactory;
use Zend\Expressive\Router\Middleware\RouteMiddleware;
use Zend\Expressive\Router\Middleware\RouteMiddlewareFactory;
use Zend\Expressive\Router\RouteCollector;
use Zend\Expressive\Router\RouteCollectorFactory;
use Zend\Expressive\Router\RouterInterface;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;
use Zend\HttpHandlerRunner\RequestHandlerRunner;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
use Zend\Stratigility\MiddlewarePipe;

final class ZendExpressiveTest extends AbstractMiddlewareRunnerTest
{
    final public function testContainsConfigCollectorOutput(): void
    {
        $response = $this->dispatchApplication([
            'REQUEST_URI' => '/hello',
            'REQUEST_METHOD' => 'GET',
            'HTTP_ACCEPT' => 'text/html',
        ], [
            '/hello' => function (ServerRequestInterface $request) {
                $response = new Response();
                $response->getBody()->write('Hello!');
                return $response;
            },
        ]);

        $responseBody = (string) $response->getBody();

        $this->assertContains('DebugBar\\\DataCollector\\\ConfigCollector', $responseBody);
    }

    protected function dispatchApplication(array $server, array $pipe = []): ResponseInterface
    {
        $container = $this->createContainer($server);

        $appFactory = new ApplicationFactory();
        $app = $appFactory($container);

        $app->pipe(RouteMiddleware::class);

        $app->pipe(PhpDebugBarMiddleware::class);

        $app->pipe(DispatchMiddleware::class);

        foreach ($pipe as $pattern => $middleware) {
            $app->get($pattern, $middleware);
        }

        $app->run();

        return $container->get(EmitterInterface::class)->getResponse();
    }

    private function createContainer(array $server): ContainerInterface
    {
        $config = ConfigProvider::getConfig();
        $config['debug'] = true;

        $serviceManagerConfig = $config['dependencies'];
        $serviceManagerConfig['services']['config'] = $config;
        $serviceManagerConfig['services'][EmitterInterface::class] = new TestEmitter();
        $serviceManagerConfig['services'][ServerRequestInterface::class] = function() use ($server) {
            return ServerRequestFactory::fromGlobals($server, [], [], [], []);
        };
        $serviceManagerConfig['factories'][MiddlewareFactory::class] = MiddlewareFactoryFactory::class;
        $serviceManagerConfig['factories'][MiddlewareContainer::class] = MiddlewareContainerFactory::class;
        $serviceManagerConfig['factories'][MiddlewarePipe::class] = InvokableFactory::class;
        $serviceManagerConfig['factories'][RouteCollector::class] = RouteCollectorFactory::class;
        $serviceManagerConfig['factories'][FastRouteRouter::class] = FastRouteRouterFactory::class;
        $serviceManagerConfig['factories'][RequestHandlerRunner::class] = RequestHandlerRunnerFactory::class;
        $serviceManagerConfig['factories'][ServerRequestErrorResponseGenerator::class] = ServerRequestErrorResponseGeneratorFactory::class;
        $serviceManagerConfig['factories'][ResponseInterface::class] = ResponseFactoryFactory::class;
        $serviceManagerConfig['factories'][RouteMiddleware::class] = RouteMiddlewareFactory::class;
        $serviceManagerConfig['factories'][DispatchMiddleware::class] = DispatchMiddlewareFactory::class;
        $serviceManagerConfig['factories'][ResponseFactory::class] = InvokableFactory::class;
        $serviceManagerConfig['factories'][StreamFactory::class] = InvokableFactory::class;
        $serviceManagerConfig['aliases'][RouterInterface::class] = FastRouteRouter::class;
        $serviceManagerConfig['aliases'][\Zend\Expressive\ApplicationPipeline::class] = MiddlewarePipe::class;
        $serviceManagerConfig['aliases'][ResponseFactoryInterface::class] = ResponseFactory::class;
        $serviceManagerConfig['aliases'][StreamFactoryInterface::class] = StreamFactory::class;

        return new ServiceManager($serviceManagerConfig);
    }
}
