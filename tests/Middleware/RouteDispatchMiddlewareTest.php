<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Router\Middleware;

use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Router\Middleware\RouteDispatchMiddleware;
use HttpSoft\Router\Route;
use HttpSoft\Runner\MiddlewareResolver;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function get_class;

class RouteDispatchMiddlewareTest extends TestCase
{
    /**
     * @var ServerRequest
     */
    private ServerRequest $request;

    /**
     * @var RouteDispatchMiddleware
     */
    private RouteDispatchMiddleware $middleware;

    public function setUp(): void
    {
        $this->request = new ServerRequest();
        $this->middleware = new RouteDispatchMiddleware(new MiddlewareResolver());
    }

    public function testProcessWithoutHandler(): void
    {
        $response = $this->middleware->process($this->request, $this->failureRequestHandler());
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testProcessWithClassNameHandler(): void
    {
        $route = new Route('home', '/', get_class($this->successRequestHandler()));
        $request = $this->request->withAttribute(Route::class, $route);
        $response = $this->middleware->process($request, $this->failureRequestHandler());
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testProcessWithObjectHandler(): void
    {
        $route = new Route('home', '/', $this->successRequestHandler());
        $request = $this->request->withAttribute(Route::class, $route);
        $response = $this->middleware->process($request, $this->failureRequestHandler());
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testProcessWithClosureHandler(): void
    {
        $route = new Route('home', '/', fn(): ResponseInterface => new Response(201));
        $request = $this->request->withAttribute(Route::class, $route);
        $response = $this->middleware->process($request, $this->failureRequestHandler());
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testProcessWithArrayHandler(): void
    {
        $route = new Route('home', '/', [
            get_class($this->middleware()),
            $this->successRequestHandler(),
        ]);
        $request = $this->request->withAttribute(Route::class, $route);
        $response = $this->middleware->process($request, $this->failureRequestHandler());
        $this->assertSame('true', $response->getHeaderLine('X-Middleware'));
        $this->assertSame(201, $response->getStatusCode());
    }

    /**
     * @return RequestHandlerInterface
     */
    private function failureRequestHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(404);
            }
        };
    }

    /**
     * @return RequestHandlerInterface
     */
    private function successRequestHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(201);
            }
        };
    }

    /**
     * @return MiddlewareInterface
     */
    private function middleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $response = $handler->handle($request);
                return $response->withAddedHeader('X-Middleware', 'true');
            }
        };
    }
}
