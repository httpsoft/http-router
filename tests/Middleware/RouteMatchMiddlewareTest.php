<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Router\Middleware;

use HttpSoft\Message\Response;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\Uri;
use HttpSoft\Router\Middleware\RouteMatchMiddleware;
use HttpSoft\Router\Route;
use HttpSoft\Router\RouteCollector;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteMatchMiddlewareTest extends TestCase
{
    /**
     * @var callable
     */
    private $handler;

    /**
     * @var RouteCollector
     */
    private RouteCollector $router;

    /**
     * @var ServerRequest
     */
    private ServerRequest $request;

    /**
     * @var ResponseFactoryInterface
     */
    private ResponseFactoryInterface $responseFactory;

    public function setUp(): void
    {
        $this->router = new RouteCollector();
        $this->request = new ServerRequest();
        $this->responseFactory = new ResponseFactory();
        $this->handler = fn(): ResponseInterface => new Response(201);
    }

    public function testProcessSuccess(): void
    {
        $this->router->get('home', '/', $this->handler);
        $middleware = new RouteMatchMiddleware($this->router, $this->responseFactory);
        $response = $middleware->process($this->request->withUri(new Uri('/')), $this->requestHandler());
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testProcessFailure(): void
    {
        $this->router->post('home', '/', $this->handler);
        $middleware = new RouteMatchMiddleware($this->router, $this->responseFactory);
        $response = $middleware->process($this->request->withUri(new Uri('/not-exist')), $this->requestHandler());
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testProcessRouteMatchedButNotAllowedRequestMethod(): void
    {
        $this->router->post('home', '/', $this->handler);
        $middleware = new RouteMatchMiddleware($this->router, $this->responseFactory);
        $response = $middleware->process($this->request->withUri(new Uri('/')), $this->requestHandler());
        $this->assertSame('POST, HEAD', $response->getHeaderLine('Allow'));
        $this->assertSame(405, $response->getStatusCode());
    }

    public function testProcessSuccessWithAllowedMethods(): void
    {
        $this->router->post('page', '/{page}', $this->handler);
        $middleware = new RouteMatchMiddleware($this->router, $this->responseFactory, ['GET']);
        $response = $middleware->process($this->request->withUri(new Uri('/test')), $this->requestHandler());
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testProcessSuccessWithAllowedMethodHeadByDefault(): void
    {
        $this->router->get('home', '/', $this->handler);
        $middleware = new RouteMatchMiddleware($this->router, $this->responseFactory);
        $response = $middleware->process(
            $this->request->withMethod('HEAD')->withUri(new Uri('/')),
            $this->requestHandler()
        );
        $this->assertSame(201, $response->getStatusCode());
    }

    /**
     * @return RequestHandlerInterface
     */
    private function requestHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if ($route = $request->getAttribute(Route::class)) {
                    return ($route->getHandler())();
                }

                return new Response(404);
            }
        };
    }
}
