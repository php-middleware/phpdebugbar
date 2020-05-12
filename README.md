# phpdebugbar middleware [![Build Status](https://travis-ci.org/php-middleware/phpdebugbar.svg?branch=master)](https://travis-ci.org/php-middleware/phpdebugbar)
[PHP Debug Bar](http://phpdebugbar.com/) as framework-agnostic [PSR-15 middleware](https://www.php-fig.org/psr/psr-15/) with [PSR-7 messages](https://www.php-fig.org/psr/psr-7/) created by [PSR-17 message factories](https://www.php-fig.org/psr/psr-17/). Also provides [PSR-11 container invokable factories](https://www.php-fig.org/psr/psr-11/).

Framework-agnostic way to attach [PHP Debug Bar](http://phpdebugbar.com/) to your response (html or non-html!).

## Installation

```
composer require --dev php-middleware/php-debug-bar
```

To build middleware you need to inject `DebugBar\JavascriptRenderer` (you can get it from `DebugBar\StandardDebugBar`) inside `PhpDebugBarMiddleware` and add it into your middleware runner:

```php
$debugbar = new DebugBar\StandardDebugBar();
$debugbarRenderer = $debugbar->getJavascriptRenderer('/phpdebugbar');
$middleware = new PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware($debugbarRenderer, $psr17ResponseFactory, $psr17StreamFactory);

// or use provided factory
$factory = new PhpMiddleware\PhpDebugBar\PhpDebugBarMiddlewareFactory();
$middleware = $factory($psr11Container);

$app = new MiddlewareRunner();
$app->add($middleware);
$app->run($request, $response);
```

You don't need to copy any static assets from phpdebugbar vendor!

### How to force disable or enable PHP Debug Bar?

Sometimes you want to have control when enable or disable PHP Debug Bar:
* custom content negotiation,
* allow debug redirects responses.

We allow you to disable attaching phpdebugbar using `X-Enable-Debug-Bar: false` header, cookie or request attribute.
To force enable just send request with `X-Enable-Debug-Bar` header, cookie or request attribute with `true` value.

### PSR-17

This package isn't require any PSR-7 implementation - you need to provide it by own. Middleware require ResponseFactory and StreamFactory interfaces. [List of existing interfaces](https://packagist.org/providers/psr/http-factory-implementation).

#### ... and PSR-11

If you use provided PSR-11 factories, then your container must have services registered as PSR-17 interface's name. Example for [laminas-diactoros](https://github.com/laminas/laminas-diactoros) implementation and [Pimple](https://pimple.symfony.com/):

```php
$container[Psr\Http\Message\ResponseInterface::class] = new Laminas\Diactoros\ResponseFactory();
$container[Psr\Http\Message\StreamFactoryInterface::class] = new Laminas\Diactoros\StreamFactory();
```

### How to install on Mezzio?

You need to register `PhpMiddleware\PhpDebugBar\ConfigProvider` and pipe provided middleware:

```php
$app->pipe(\PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware::class);
```

For more - follow Mezzio [documentation](https://docs.mezzio.dev/mezzio/v3/features/modular-applications/).

### How to install on Slim 3?

Register factories in container:

```php
foreach (ConfigProvider::getConfig()['dependencies']['factories'] as $key => $factory) {
    $container[$key] = new $factory();
}
```

and add middleware from container to app:

```php
$app->add(
    $app->getContainer()->get(\PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware::class)
);
```

### How to configure using existing factories?

Put array with a configuration into `PhpMiddleware\PhpDebugBar\ConfigProvider` service in your container:

```php
return [
    'phpmiddleware' => [
        'phpdebugbar' => [
            'javascript_renderer' => [
                'base_url' => '/phpdebugbar',
            ],
            'collectors' => [
                DebugBar\DataCollector\ConfigCollector::class, // Service names of collectors
            ],
            'storage' => null, // Service name of storage
        ],
    ],
];
```

You can override existing configuration by merge default configuration with your own (example):

```php
return array_merge(PhpMiddleware\PhpDebugBar\ConfigProvider::getConfig(), $myOverritenConfig);
```

## It's just works with any modern php framework!

Middleware tested on:
* [Mezzio](https://github.com/mezzio/mezzio)
* [Slim 3.x](https://github.com/slimphp/Slim)

And any other modern framework [supported PSR-17 middlewares and PSR-7](https://mwop.net/blog/2015-01-08-on-http-middleware-and-psr-7.html).
