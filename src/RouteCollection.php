<?php

declare(strict_types=1);

namespace HttpSoft\Router;

use ArrayIterator;
use HttpSoft\Router\Exception\RouteAlreadyExistsException;
use HttpSoft\Router\Exception\RouteNotFoundException;
use Psr\Http\Message\ServerRequestInterface;

use function count;

final class RouteCollection implements RouteCollectionInterface
{
    /**
     * @var Route[]
     */
    private array $routes = [];

    /**
     * {@inheritDoc}
     */
    public function set(Route $route): void
    {
        $name = $route->getName();

        if ($this->has($name)) {
            throw RouteAlreadyExistsException::create($name);
        }

        $this->routes[$name] = $route;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $name): Route
    {
        if (!$this->has($name)) {
            throw RouteNotFoundException::create($name);
        }

        return $this->routes[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(): array
    {
        return $this->routes;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->routes);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $name): bool
    {
        return isset($this->routes[$name]);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $name): Route
    {
        if (!$this->has($name)) {
            throw new RouteNotFoundException($name);
        }

        $removed = $this->routes[$name];
        unset($this->routes[$name]);

        return $removed;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->routes = [];
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return count($this->routes);
    }

    /**
     * {@inheritDoc}
     */
    public function match(ServerRequestInterface $request, bool $checkAllowedMethods = true): ?Route
    {
        $routeWithNotAllowedMethod = null;

        foreach ($this->routes as $route) {
            if (!$route->match($request)) {
                continue;
            }

            if ($route->isAllowedMethod($request->getMethod())) {
                return $route;
            }

            if ($routeWithNotAllowedMethod === null) {
                $routeWithNotAllowedMethod = $route;
            }
        }

        return $checkAllowedMethods ? null : $routeWithNotAllowedMethod;
    }

    /**
     * {@inheritDoc}
     */
    public function path(string $name, array $parameters = []): string
    {
        $route = $this->get($name);
        return $route->path($parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function url(string $name, array $parameters = [], string $host = null, bool $secure = null): string
    {
        $route = $this->get($name);
        return $route->url($parameters, $host, $secure);
    }
}
