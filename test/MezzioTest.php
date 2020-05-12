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
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Mezzio\Container\ApplicationFactory;
use Mezzio\Container\MiddlewareContainerFactory;
use Mezzio\Container\MiddlewareFactoryFactory;
use Mezzio\Container\RequestHandlerRunnerFactory;
use Mezzio\Container\ResponseFactoryFactory;
use Mezzio\Container\ServerRequestErrorResponseGeneratorFactory;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\FastRouteRouterFactory;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\DispatchMiddlewareFactory;
use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Router\Middleware\RouteMiddlewareFactory;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouteCollectorFactory;
use Mezzio\Router\RouterInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stratigility\MiddlewarePipe;

final class MezzioTest extends AbstractMiddlewareRunnerTest
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

        $this->assertStringContainsString('DebugBar\\\DataCollector\\\ConfigCollector', $responseBody);
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
        $serviceManagerConfig['aliases'][\Mezzio\ApplicationPipeline::class] = MiddlewarePipe::class;
        $serviceManagerConfig['aliases'][ResponseFactoryInterface::class] = ResponseFactory::class;
        $serviceManagerConfig['aliases'][StreamFactoryInterface::class] = StreamFactory::class;

        return new ServiceManager($serviceManagerConfig);
    }
}
