<?php

declare(strict_types=1);

namespace HttpSoft\Router;

use Countable;
use HttpSoft\Router\Exception\RouteAlreadyExistsException;
use HttpSoft\Router\Exception\RouteNotFoundException;
use IteratorAggregate;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @psalm-suppress MissingTemplateParam
 */
interface RouteCollectionInterface extends Countable, IteratorAggregate
{
    /**
     * Sets a route.
     *
     * @param Route $route route to set.
     * @throws RouteAlreadyExistsException if the route already exists.
     */
    public function set(Route $route): void;

    /**
     * Gets the route with the specified name.
     *
     * @param string $name route name.
     * @return Route
     * @throws RouteNotFoundException if the route does not exist.
     */
    public function get(string $name): Route;

    /**
     * Gets all routes.
     *
     * @return Route[] all routes, or an empty array if no routes exist.
     */
    public function getAll(): array;

    /**
     * Whether a route with the specified name exists.
     *
     * @param string $name route name.
     * @return bool whether the named route exists.
     */
    public function has(string $name): bool;

    /**
     * Removes a route.
     *
     * @param string $name name of the route to be removed.
     * @return Route route that is removed.
     * @throws RouteNotFoundException if the route does not exist.
     */
    public function remove(string $name): Route;

    /**
     * Removes all routes.
     */
    public function clear(): void;

    /**
     * Matches the request against known routes.
     *
     * @param ServerRequestInterface $request
     * @param bool $checkAllowedMethods whether to check if the request method matches the allowed route methods.
     * @return Route matched route or null if the request does not match the routes.
     */
    public function match(ServerRequestInterface $request, bool $checkAllowedMethods = true): ?Route;

    /**
     * Generates the URL path from the named route and parameters.
     *
     * @param string $name name of the route.
     * @param array $parameters parameter-value set.
     * @return string URL path generated.
     * @throws RouteNotFoundException if the route does not exist.
     */
    public function path(string $name, array $parameters = []): string;

    /**
     * Generates the URL from the named route and parameters.
     *
     * @param string $name name of the route.
     * @param array $parameters parameter-value set.
     * @param string|null $host host component of the URI.
     * @param bool|null $secure If `true`, then `https`. If `false`, then `http`. If `null`, then without the protocol.
     * @return string URL generated.
     * @throws RouteNotFoundException if the route does not exist.
     */
    public function url(string $name, array $parameters = [], ?string $host = null, ?bool $secure = null): string;
}
