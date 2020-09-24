<?php

declare(strict_types=1);

namespace HttpSoft\Router\Middleware;

use HttpSoft\Router\Route;
use HttpSoft\Runner\MiddlewareResolverInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RouteDispatchMiddleware implements MiddlewareInterface
{
    /**
     * @var MiddlewareResolverInterface
     */
    private MiddlewareResolverInterface $resolver;

    /**
     * @param MiddlewareResolverInterface $resolver
     */
    public function __construct(MiddlewareResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-suppress MixedAssignment
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$result = $request->getAttribute(Route::class)) {
            return $handler->handle($request);
        }

        if ($result instanceof Route) {
            $result = $result->getHandler();
        }

        $middleware = $this->resolver->resolve($result);
        return $middleware->process($request, $handler);
    }
}
