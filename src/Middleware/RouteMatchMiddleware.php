<?php

declare(strict_types=1);

namespace HttpSoft\Router\Middleware;

use HttpSoft\Router\Route;
use HttpSoft\Router\RouteCollector;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_filter;
use function array_unique;
use function implode;
use function in_array;
use function is_string;
use function strtoupper;

final class RouteMatchMiddleware implements MiddlewareInterface
{
    /**
     * @var RouteCollector
     */
    private RouteCollector $router;

    /**
     * @var ResponseFactoryInterface
     */
    private ResponseFactoryInterface $responseFactory;

    /**
     * @var string[]
     */
    private array $allowedMethods = [];

    /**
     * @param RouteCollector $router
     * @param ResponseFactoryInterface $responseFactory
     * @param array|string[] $allowedMethods common allowed request methods for all routes.
     * @psalm-suppress MixedAssignment
     */
    public function __construct(
        RouteCollector $router,
        ResponseFactoryInterface $responseFactory,
        array $allowedMethods = ['HEAD']
    ) {
        $this->router = $router;
        $this->responseFactory = $responseFactory;

        foreach ($allowedMethods as $allowedMethod) {
            if (is_string($allowedMethod)) {
                $this->allowedMethods[] = strtoupper($allowedMethod);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$route = $this->router->routes()->match($request, false)) {
            return $handler->handle($request);
        }

        if (!$this->isAllowedMethods($request->getMethod()) && !$route->isAllowedMethod($request->getMethod())) {
            return $this->getEmptyResponseWithAllowedMethods($route->getMethods());
        }

        foreach ($route->getMatchedParameters() as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        return $handler->handle($request->withAttribute(Route::class, $route));
    }

    /**
     * @param string[] $methods
     * @return ResponseInterface
     */
    private function getEmptyResponseWithAllowedMethods(array $methods): ResponseInterface
    {
        foreach ($this->allowedMethods as $method) {
            $methods[] = $method;
        }

        $methods = implode(', ', array_unique(array_filter($methods)));
        return $this->responseFactory->createResponse(405)->withHeader('Allow', $methods);
    }

    /**
     * @param string $method
     * @return bool
     */
    private function isAllowedMethods(string $method): bool
    {
        return ($this->allowedMethods !== [] && in_array(strtoupper($method), $this->allowedMethods, true));
    }
}
