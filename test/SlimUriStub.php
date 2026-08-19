<?php
declare (strict_types=1);

namespace PhpMiddlewareTest\PhpDebugBar;

use Psr\Http\Message\UriInterface;

/**
 * Mimics Slim 3 Slim\Http\Uri: a PSR-7 URI with an extra getBasePath() method,
 * used to cover the duck-typed Slim3 branch of PhpDebugBarMiddleware::extractPath()
 * without depending on slim/slim 3. Parameters are untyped on purpose so the
 * stub satisfies both psr/http-message ^1.0 and ^2.0.
 */
final class SlimUriStub implements UriInterface
{
    /** @var string */
    private $basePath;

    /** @var string */
    private $path;

    public function __construct(string $basePath, string $path)
    {
        $this->basePath = $basePath;
        $this->path = $path;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getScheme(): string
    {
        return 'http';
    }

    public function getAuthority(): string
    {
        return 'example.com';
    }

    public function getUserInfo(): string
    {
        return '';
    }

    public function getHost(): string
    {
        return 'example.com';
    }

    public function getPort(): ?int
    {
        return null;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return '';
    }

    public function getFragment(): string
    {
        return '';
    }

    public function withScheme($scheme): UriInterface
    {
        return $this;
    }

    public function withUserInfo($user, $password = null): UriInterface
    {
        return $this;
    }

    public function withHost($host): UriInterface
    {
        return $this;
    }

    public function withPort($port): UriInterface
    {
        return $this;
    }

    public function withPath($path): UriInterface
    {
        return $this;
    }

    public function withQuery($query): UriInterface
    {
        return $this;
    }

    public function withFragment($fragment): UriInterface
    {
        return $this;
    }

    public function __toString(): string
    {
        return $this->basePath . $this->path;
    }
}
