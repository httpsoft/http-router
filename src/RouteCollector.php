<?php

declare(strict_types=1);

namespace HttpSoft\Router;

final class RouteCollector
{
    /**
     * @var string
     */
    private string $currentGroupPrefix = '';

    /**
     * @var RouteCollectionInterface
     */
    private RouteCollectionInterface $routes;

    /**
     * @param RouteCollectionInterface|null $routes
     */
    public function __construct(RouteCollectionInterface $routes = null)
    {
        $this->routes = $routes ?? new RouteCollection();
    }

    /**
     * Gets an instance of the `RouteCollectionInterface` with all routes set.
     *
     * @return RouteCollectionInterface
     */
    public function routes(): RouteCollectionInterface
    {
        return $this->routes;
    }

    /**
     * Creates a route group with a common prefix.
     *
     * The callback can take a RouteCollector instance as a parameter.
     * All routes created in the passed callback will have the given group prefix prepended.
     *
     * @param string $prefix common path prefix for the route group.
     * @param callable $callback callback that will add routes with a common path prefix.
     */
    public function group(string $prefix, callable $callback): void
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;
        $callback($this);
        $this->currentGroupPrefix = $previousGroupPrefix;
    }

    /**
     * Adds a route and returns it.
     *
     * @param string $name route name.
     * @param string $pattern path pattern with parameters.
     * @param mixed $handler action, controller, callable, closure, etc.
     * @param array $methods allowed request methods of the route.
     * @return Route
     */
    public function add(string $name, string $pattern, $handler, array $methods): Route
    {
        $pattern = $this->currentGroupPrefix . $pattern;
        $route = new Route($name, $pattern, $handler, $methods);
        $this->routes->set($route);
        return $route;
    }

    /**
     * Adds a generic route for any request methods and returns it.
     *
     * @param string $name route name.
     * @param string $pattern path pattern with parameters.
     * @param mixed $handler action, controller, callable, closure, etc.
     * @return Route
     */
    public function any(string $name, string $pattern, $handler): Route
    {
        return $this->add($name, $pattern, $handler, []);
    }

    /**
     * Adds a GET route and returns it.
     *
     * @param string $name route name.
     * @param string $pattern path pattern with parameters.
     * @param mixed $handler action, controller, callable, closure, etc.
     * @return Route
     */
    public function get(string $name, string $pattern, $handler): Route
    {
        return $this->add($name, $pattern, $handler, ['GET']);
    }

    /**
     * Adds a POST route and returns it.
     *
     * @param string $name route name.
     * @param string $pattern path pattern with parameters.
     * @param mixed $handler action, controller, callable, closure, etc.
     * @return Route
     */
    public function post(string $name, string $pattern, $handler): Route
    {
        return $this->add($name, $pattern, $handler, ['POST']);
    }

    /**
     * Adds a PUT route and returns it.
     *
     * @param string $name route name.
     * @param string $pattern path pattern with parameters.
     * @param mixed $handler action, controller, callable, closure, etc.
     * @return Route
     */
    public function put(string $name, string $pattern, $handler): Route
    {
        return $this->add($name, $pattern, $handler, ['PUT']);
    }

    /**
     * Adds a PATCH route and returns it.
     *
     * @param string $name route name.
     * @param string $pattern path pattern with parameters.
     * @param mixed $handler action, controller, callable, closure, etc.
     * @return Route
     */
    public function patch(string $name, string $pattern, $handler): Route
    {
        return $this->add($name, $pattern, $handler, ['PATCH']);
    }

    /**
     * Adds a DELETE route and returns it.
     *
     * @param string $name route name.
     * @param string $pattern path pattern with parameters.
     * @param mixed $handler action, controller, callable, closure, etc.
     * @return Route
     */
    public function delete(string $name, string $pattern, $handler): Route
    {
        return $this->add($name, $pattern, $handler, ['DELETE']);
    }

    /**
     * Adds a HEAD route and returns it.
     *
     * @param string $name route name.
     * @param string $pattern path pattern with parameters.
     * @param mixed $handler action, controller, callable, closure, etc.
     * @return Route
     */
    public function head(string $name, string $pattern, $handler): Route
    {
        return $this->add($name, $pattern, $handler, ['HEAD']);
    }

    /**
     * Adds a OPTIONS route and returns it.
     *
     * @param string $name route name.
     * @param string $pattern path pattern with parameters.
     * @param mixed $handler action, controller, callable, closure, etc.
     * @return Route
     */
    public function options(string $name, string $pattern, $handler): Route
    {
        return $this->add($name, $pattern, $handler, ['OPTIONS']);
    }
}
