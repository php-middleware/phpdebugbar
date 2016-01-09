# phpdebugbar middleware [![Build Status](https://travis-ci.org/php-middleware/phpdebugbar.svg?branch=master)](https://travis-ci.org/php-middleware/phpdebugbar)
PHP Debug bar middleware with PSR-7

This middleware provide framework-agnostic possibility to attach [PHP Debug bar](http://phpdebugbar.com/) to your response (html on non-html!).

## Installation

```json
{
    "require": {
        "php-middleware/php-debug-bar": "^1.1.0"
    }
}
```

To build this middleware you need to injecting inside `PhpDebugBarMiddleware` instance `DebugBar\JavascriptRenderer` (you can get it from `DebugBar\StandardDebugBar`) and add middleware to your middleware runner. Or use default factory.

```php
$debugbar = new DebugBar\StandardDebugBar();
$debugbarRenderer = $debugbar->getJavascriptRenderer('/phpdebugbar');
$middleware = new PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware($debugbarRenderer);

// OR

$factory = PhpMiddleware\PhpDebugBar\PhpDebugBarMiddlewareFactory();
$middleware = $factory();

$app = new MiddlewareRunner();
$app->add($middleware);
$app->run($request, $response);
```

You don't need to copy any static assets from phpdebugbar vendor!

## It's just works with any modern php framework!

Middleware tested on:
* [Expressive](https://github.com/zendframework/zend-expressive)

Middleware should works with:
* [Slim 3.x](https://github.com/slimphp/Slim)

And any other modern framework [supported middlewares and PSR-7](https://mwop.net/blog/2015-01-08-on-http-middleware-and-psr-7.html).
