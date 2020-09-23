<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Router;

use HttpSoft\Message\Response;
use HttpSoft\Router\RouteCollector;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class RouteCollectorTest extends TestCase
{
    /**
     * @var RouteCollector
     */
    private RouteCollector $router;

    /**
     * @var callable
     */
    private $handler;

    public function setUp(): void
    {
        $this->router = new RouteCollector();
        $this->handler = fn(): ResponseInterface => new Response();
    }

    public function testAdd(): void
    {
        $route = $this->router->add($name = 'test', $pattern = '/path', $this->handler, $methods = ['GET', 'PUT']);

        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());
        $this->assertSame($methods, $route->getMethods());

        $this->assertTrue($route->isAllowedMethod('GET'));
        $this->assertFalse($route->isAllowedMethod('POST'));
        $this->assertTrue($route->isAllowedMethod('PUT'));
        $this->assertFalse($route->isAllowedMethod('PATCH'));
        $this->assertFalse($route->isAllowedMethod('DELETE'));
        $this->assertFalse($route->isAllowedMethod('HEAD'));
        $this->assertFalse($route->isAllowedMethod('OPTIONS'));
    }

    public function testAny(): void
    {
        $route = $this->router->any($name = 'test', $pattern = '/path', $this->handler);

        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());
        $this->assertSame([], $route->getMethods());

        $this->assertTrue($route->isAllowedMethod('GET'));
        $this->assertTrue($route->isAllowedMethod('POST'));
        $this->assertTrue($route->isAllowedMethod('PUT'));
        $this->assertTrue($route->isAllowedMethod('PATCH'));
        $this->assertTrue($route->isAllowedMethod('DELETE'));
        $this->assertTrue($route->isAllowedMethod('HEAD'));
        $this->assertTrue($route->isAllowedMethod('OPTIONS'));
    }

    public function testGet(): void
    {
        $route = $this->router->get($name = 'test', $pattern = '/path', $this->handler);

        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());
        $this->assertSame(['GET'], $route->getMethods());

        $this->assertTrue($route->isAllowedMethod('GET'));
        $this->assertFalse($route->isAllowedMethod('POST'));
        $this->assertFalse($route->isAllowedMethod('PUT'));
        $this->assertFalse($route->isAllowedMethod('PATCH'));
        $this->assertFalse($route->isAllowedMethod('DELETE'));
        $this->assertFalse($route->isAllowedMethod('HEAD'));
        $this->assertFalse($route->isAllowedMethod('OPTIONS'));
    }

    public function testPost(): void
    {
        $route = $this->router->post($name = 'test', $pattern = '/path', $this->handler);

        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());
        $this->assertSame(['POST'], $route->getMethods());

        $this->assertFalse($route->isAllowedMethod('GET'));
        $this->assertTrue($route->isAllowedMethod('POST'));
        $this->assertFalse($route->isAllowedMethod('PUT'));
        $this->assertFalse($route->isAllowedMethod('PATCH'));
        $this->assertFalse($route->isAllowedMethod('DELETE'));
        $this->assertFalse($route->isAllowedMethod('HEAD'));
        $this->assertFalse($route->isAllowedMethod('OPTIONS'));
    }

    public function testPut(): void
    {
        $route = $this->router->put($name = 'test', $pattern = '/path', $this->handler);

        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());
        $this->assertSame(['PUT'], $route->getMethods());

        $this->assertFalse($route->isAllowedMethod('GET'));
        $this->assertFalse($route->isAllowedMethod('POST'));
        $this->assertTrue($route->isAllowedMethod('PUT'));
        $this->assertFalse($route->isAllowedMethod('PATCH'));
        $this->assertFalse($route->isAllowedMethod('DELETE'));
        $this->assertFalse($route->isAllowedMethod('HEAD'));
        $this->assertFalse($route->isAllowedMethod('OPTIONS'));
    }

    public function testPatch(): void
    {
        $route = $this->router->patch($name = 'test', $pattern = '/path', $this->handler);

        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());
        $this->assertSame(['PATCH'], $route->getMethods());

        $this->assertFalse($route->isAllowedMethod('GET'));
        $this->assertFalse($route->isAllowedMethod('POST'));
        $this->assertFalse($route->isAllowedMethod('PUT'));
        $this->assertTrue($route->isAllowedMethod('PATCH'));
        $this->assertFalse($route->isAllowedMethod('DELETE'));
        $this->assertFalse($route->isAllowedMethod('HEAD'));
        $this->assertFalse($route->isAllowedMethod('OPTIONS'));
    }

    public function testDelete(): void
    {
        $route = $this->router->delete($name = 'test', $pattern = '/path', $this->handler);

        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());
        $this->assertSame(['DELETE'], $route->getMethods());

        $this->assertFalse($route->isAllowedMethod('GET'));
        $this->assertFalse($route->isAllowedMethod('POST'));
        $this->assertFalse($route->isAllowedMethod('PUT'));
        $this->assertFalse($route->isAllowedMethod('PATCH'));
        $this->assertTrue($route->isAllowedMethod('DELETE'));
        $this->assertFalse($route->isAllowedMethod('HEAD'));
        $this->assertFalse($route->isAllowedMethod('OPTIONS'));
    }

    public function testHead(): void
    {
        $route = $this->router->head($name = 'test', $pattern = '/path', $this->handler);

        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());
        $this->assertSame(['HEAD'], $route->getMethods());

        $this->assertFalse($route->isAllowedMethod('GET'));
        $this->assertFalse($route->isAllowedMethod('POST'));
        $this->assertFalse($route->isAllowedMethod('PUT'));
        $this->assertFalse($route->isAllowedMethod('PATCH'));
        $this->assertFalse($route->isAllowedMethod('DELETE'));
        $this->assertTrue($route->isAllowedMethod('HEAD'));
        $this->assertFalse($route->isAllowedMethod('OPTIONS'));
    }

    public function testOptions(): void
    {
        $route = $this->router->options($name = 'test', $pattern = '/path', $this->handler);

        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());
        $this->assertSame(['OPTIONS'], $route->getMethods());

        $this->assertFalse($route->isAllowedMethod('GET'));
        $this->assertFalse($route->isAllowedMethod('POST'));
        $this->assertFalse($route->isAllowedMethod('PUT'));
        $this->assertFalse($route->isAllowedMethod('PATCH'));
        $this->assertFalse($route->isAllowedMethod('DELETE'));
        $this->assertFalse($route->isAllowedMethod('HEAD'));
        $this->assertTrue($route->isAllowedMethod('OPTIONS'));
    }

    public function testGroups(): void
    {
        $group = [];
        $router = $this->router;
        $handler = $this->handler;
        $dash = $underline = null;

        $get = $router->get('get', '/get', $handler);
        $post = $router->post('post', '/post', $handler);
        $put = $router->put('put', '/put', $handler);
        $patch = $router->patch('patch', '/patch', $handler);
        $delete = $router->delete('delete', '/delete', $handler);
        $head = $router->head('head', '/head', $handler);
        $options = $router->options('options', '/options', $handler);

        $router->group('/group-one', static function (RouteCollector $router) use (&$group, $handler): void {
            $group['get_one'] = $router->get('get-one', '/get', $handler);
            $group['post_one'] = $router->post('post-one', '/post', $handler);
            $group['put_one'] = $router->put('put-one', '/put', $handler);
            $group['patch_one'] = $router->patch('patch-one', '/patch', $handler);
            $group['delete_one'] = $router->delete('delete-one', '/delete', $handler);
            $group['head_one'] = $router->head('head-one', '/head', $handler);
            $group['options_one'] = $router->options('options-one', '/options', $handler);

            $router->group('/group-two', static function (RouteCollector $router) use (&$group, $handler): void {
                $group['get_two'] = $router->get('get-two', '/get', $handler);
                $group['post_two'] = $router->post('post-two', '/post', $handler);
                $group['put_two'] = $router->put('put-two', '/put', $handler);
                $group['patch_two'] = $router->patch('patch-two', '/patch', $handler);
                $group['delete_two'] = $router->delete('delete-two', '/delete', $handler);
                $group['head_two'] = $router->head('head-two', '/head', $handler);
                $group['options_two'] = $router->options('options-two', '/options', $handler);
            });
        });

        $router->group('/prefix', static function (RouteCollector $router) use (&$dash, $handler): void {
            $dash = $router->get('dash-path', '-dash-path', $handler);
        });
        $router->group('/prefix_', static function (RouteCollector $router) use (&$underline, $handler): void {
            $underline = $router->get('underline_path', 'underline_path', $handler);
        });

        $expected = [
            $get->getName() => $get,
            $post->getName() => $post,
            $put->getName() => $put,
            $patch->getName() => $patch,
            $delete->getName() => $delete,
            $head->getName() => $head,
            $options->getName() => $options,
            $group['get_one']->getName() => $group['get_one'],
            $group['post_one']->getName() => $group['post_one'],
            $group['put_one']->getName() => $group['put_one'],
            $group['patch_one']->getName() => $group['patch_one'],
            $group['delete_one']->getName() => $group['delete_one'],
            $group['head_one']->getName() => $group['head_one'],
            $group['options_one']->getName() => $group['options_one'],
            $group['get_two']->getName() => $group['get_two'],
            $group['post_two']->getName() => $group['post_two'],
            $group['put_two']->getName() => $group['put_two'],
            $group['patch_two']->getName() => $group['patch_two'],
            $group['delete_two']->getName() => $group['delete_two'],
            $group['head_two']->getName() => $group['head_two'],
            $group['options_two']->getName() => $group['options_two'],
            $dash->getName() => $dash,
            $underline->getName() => $underline,
        ];

        $this->assertSame($expected, $this->router->routes()->getAll());
    }
}
