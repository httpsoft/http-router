<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Router;

use ArrayIterator;
use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\Uri;
use HttpSoft\Router\Exception\InvalidRouteParameterException;
use HttpSoft\Router\Exception\RouteAlreadyExistsException;
use HttpSoft\Router\Exception\RouteNotFoundException;
use HttpSoft\Router\Route;
use HttpSoft\Router\RouteCollection;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use stdClass;

use function count;

class RouteCollectionTest extends TestCase
{
    /**
     * @var array
     */
    private array $routes;

    /**
     * @var RouteCollection
     */
    private RouteCollection $collection;

    /**
     * @var Route
     */
    private Route $home;

    /**
     * @var Route
     */
    private Route $page;

    public function setUp(): void
    {
        $this->collection = new RouteCollection();
        $handler = fn(): ResponseInterface => new Response();
        $this->home = new Route('home', '/', $handler, ['GET']);
        $this->page = (new Route('page', '/page/{require}{[optional]}', $handler, ['GET', 'POST']))
            ->tokens(['require' => '[\w-]+', 'optional' => '\d+'])
        ;
        $this->routes = [
            $this->home->getName() => $this->home,
            $this->page->getName() => $this->page,
        ];
    }

    public function testGetters(): void
    {
        $this->collection->set($this->home);
        $this->collection->set($this->page);

        $this->assertSame($this->routes, $this->collection->getAll());
        $this->assertSame(count($this->routes), $this->collection->count());
        $this->assertInstanceOf(ArrayIterator::class, $this->collection->getIterator());
        $this->assertSame(count($this->routes), $this->collection->getIterator()->count());

        $this->assertSame($this->home, $this->collection->get('home'));
        $this->assertTrue($this->collection->has('home'));
        $this->assertSame($this->home, $this->collection->remove('home'));
        $this->assertFalse($this->collection->has('home'));

        $this->assertSame($this->page, $this->collection->get('page'));
        $this->assertTrue($this->collection->has('page'));
        $this->assertSame($this->page, $this->collection->remove('page'));
        $this->assertFalse($this->collection->has('page'));
    }

    public function testSet(): void
    {
        $this->assertSame([], $this->collection->getAll());
        $this->assertSame(0, $this->collection->count());

        $this->collection->set($this->home);
        $this->collection->set($this->page);

        $this->assertSame($this->home, $this->collection->get('home'));
        $this->assertSame($this->page, $this->collection->get('page'));
        $this->assertSame($this->routes, $this->collection->getAll());
        $this->assertSame(count($this->routes), $this->collection->count());
    }

    public function testSetThrowExceptionForRouteAlreadyExists(): void
    {
        $this->collection->set($this->home);
        $this->expectException(RouteAlreadyExistsException::class);
        $this->collection->set($this->home);
    }

    public function testGetThrowExceptionForRouteNotFound(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->collection->get('route-not-found');
    }

    public function testRemove(): void
    {
        $this->collection->set($this->home);
        $this->collection->set($this->page);

        $this->assertTrue($this->collection->has('home'));
        $this->assertSame($this->home, $this->collection->remove('home'));
        $this->assertFalse($this->collection->has('home'));

        $this->assertTrue($this->collection->has('page'));
        $this->assertSame($this->page, $this->collection->remove('page'));
        $this->assertFalse($this->collection->has('page'));

        $this->assertSame([], $this->collection->getAll());
        $this->assertSame(0, $this->collection->count());
    }

    public function testRemoveThrowExceptionForRouteNotFound(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->collection->remove('route-not-found');
    }

    public function testClear(): void
    {
        $this->assertSame([], $this->collection->getAll());
        $this->assertSame(0, $this->collection->count());

        $this->collection->clear();
        $this->assertSame([], $this->collection->getAll());
        $this->assertSame(0, $this->collection->count());

        $this->collection->set($this->home);
        $this->collection->set($this->page);
        $this->assertSame($this->routes, $this->collection->getAll());
        $this->assertSame(count($this->routes), $this->collection->count());

        $this->collection->clear();
        $this->assertSame([], $this->collection->getAll());
        $this->assertSame(0, $this->collection->count());
    }

    public function testMatchWithCheckAllowedMethods(): void
    {
        $this->collection->set($this->home);
        $this->collection->set($this->page);

        $request = (new ServerRequest())->withUri(new Uri('/'));
        $this->assertSame($this->home, $this->collection->match($request));
        $this->assertNull($this->collection->match($request->withMethod('POST')));

        $request = (new ServerRequest())->withUri(new Uri('/page/require'));
        $this->assertSame($this->page, $this->collection->match($request->withMethod('GET')));
        $this->assertNull($this->collection->match($request->withMethod('DELETE')));

        $request = (new ServerRequest())->withUri(new Uri('/page/require/123'));
        $this->assertSame($this->page, $this->collection->match($request->withMethod('POST')));
        $this->assertNull($this->collection->match($request->withMethod('PATCH')));

        $request = (new ServerRequest())->withUri(new Uri('/page/require/optional-not-numbers'));
        $this->assertNull($this->collection->match($request));
        $this->assertNull($this->collection->match($request->withMethod('POST')));
    }

    public function testMatchWithoutCheckAllowedMethods(): void
    {
        $this->collection->set($this->home);
        $this->collection->set($this->page);

        $request = (new ServerRequest())->withUri(new Uri('/'));
        $this->assertSame($this->home, $this->collection->match($request, false));
        $this->assertSame($this->home, $this->collection->match($request->withMethod('POST'), false));

        $request = (new ServerRequest())->withUri(new Uri('/page/require'));
        $this->assertSame($this->page, $this->collection->match($request->withMethod('POST'), false));
        $this->assertSame($this->page, $this->collection->match($request->withMethod('DELETE'), false));

        $request = (new ServerRequest())->withUri(new Uri('/page/require/123'));
        $this->assertSame($this->page, $this->collection->match($request->withMethod('PUT'), false));
        $this->assertSame($this->page, $this->collection->match($request->withMethod('PATCH'), false));

        $request = (new ServerRequest())->withUri(new Uri('/page/require/optional-not-numbers'));
        $this->assertNull($this->collection->match($request));
        $this->assertNull($this->collection->match($request->withMethod('POST'), false));
    }

    public function testMatchWithoutCheckAllowedMethodsForSamePatternAndNotSameMethod(): void
    {
        $handler = fn(): ResponseInterface => new Response();
        $get = new Route('get', '/test', $handler, ['GET']);
        $put = new Route('put', '/test', $handler, ['PUT']);

        $this->collection->set($get);
        $this->collection->set($put);
        $request = (new ServerRequest())->withUri(new Uri('/test'));

        $this->assertSame($get, $this->collection->match($request->withMethod('GET')));
        $this->assertSame($get, $this->collection->match($request->withMethod('GET'), false));

        $this->assertSame($put, $this->collection->match($request->withMethod('PUT')));
        $this->assertSame($put, $this->collection->match($request->withMethod('PUT'), false));
    }

    public function testUrlWithoutHost(): void
    {
        $this->collection->set($this->home);
        $this->collection->set($this->page->defaults(['require' => 'default']));

        $this->assertSame('/', $this->collection->url('home'));
        $this->assertSame('/', $this->collection->url('home', ['any' => 'parameters']));
        $this->assertSame('//example.com', $this->collection->url('home', [], 'example.com'));
        $this->assertSame('//example.com', $this->collection->url('home', [], 'example.com/'));
        $this->assertSame('//example.com', $this->collection->url('home', [], '//example.com'));
        $this->assertSame('https://example.com', $this->collection->url('home', [], 'example.com', true));
        $this->assertSame('http://example.com', $this->collection->url('home', [], '//example.com', false));

        $this->assertSame('/page/default', $this->collection->url('page'));
        $this->assertSame('/page/slug', $this->collection->url('page', [
            'require' => 'slug'
        ]));
        $this->assertSame('/page/slug', $this->collection->url('page', [
            'require' => 'slug', 'optional' => null
        ]));
        $this->assertSame('//example.com/page/slug/0', $this->collection->url('page', [
            'require' => 'slug', 'optional' => 0
        ], 'example.com'));
        $this->assertSame('//example.com/page/slug/0', $this->collection->url('page', [
            'require' => 'slug', 'optional' => '0'
        ], 'example.com/'));
        $this->assertSame('//example.com/page/slug/1', $this->collection->url('page', [
            'require' => 'slug', 'optional' => 1
        ], '//example.com'));
        $this->assertSame('https://example.com/page/slug/12', $this->collection->url('page', [
            'require' => 'slug', 'optional' => 12
        ], '//example.com/', true));
        $this->assertSame('http://example.com/page/slug/123', $this->collection->url('page', [
            'require' => 'slug', 'optional' => 123
        ], '////example.com', false));
    }

    public function testUrlForRouteWithHost(): void
    {
        $this->collection->set($this->home->host('example.com'));
        $this->collection->set($this->page->host('(?:shop|blog).example.com')->defaults(['require' => 'default']));

        $this->assertSame('/', $this->collection->url('home'));
        $this->assertSame('/', $this->collection->url('home', [], ''));
        $this->assertSame('//example.com', $this->collection->url('home', [], 'example.com'));
        $this->assertSame('/', $this->collection->url('home', ['any' => 'parameters']));
        $this->assertSame('//example.com', $this->collection->url('home', ['any' => 'parameters'], '//example.com//'));
        $this->assertSame('https://example.com', $this->collection->url('home', [], 'example.com', true));
        $this->assertSame('http://example.com', $this->collection->url('home', [], 'example.com', false));

        $this->assertSame('/page/default', $this->collection->url('page'));
        $this->assertSame('/page/slug', $this->collection->url('page', ['require' => 'slug']));

        $this->assertSame('//shop.example.com/page/slug', $this->collection->url('page', [
            'require' => 'slug', 'optional' => null
        ], 'shop.example.com'));
        $this->assertSame('//blog.example.com/page/slug/0', $this->collection->url('page', [
            'require' => 'slug', 'optional' => 0
        ], 'blog.example.com'));
        $this->assertSame('https://shop.example.com/page/slug/0', $this->collection->url('page', [
            'require' => 'slug', 'optional' => '0'
        ], 'shop.example.com/', true));
        $this->assertSame('http://blog.example.com/page/slug/1', $this->collection->url('page', [
            'require' => 'slug', 'optional' => 1
        ], '//blog.example.com', false));

        $this->expectException(InvalidRouteParameterException::class);
        $this->collection->url('page', ['require' => 'slug', 'optional' => 1], 'forum.example.com');
    }

    public function testUrlThrowExceptionForRouteNotFound(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->collection->url('route-not-found');
    }

    /**
     * @return array
     */
    public function invalidUriProvider(): array
    {
        return [
            'not-scalar-array' => [ ['require' => 'slug', 'optional' => [1]] ],
            'not-scalar-object' => [ ['require' => new StdClass()] ],
            'not-scalar-callable' => [ ['require' => fn() => null] ],
            'require-not-passed' => [ ['optional' => 123] ],
            'require-null' => [ ['require' => null, 'optional' => 123] ],
            'require-failure' => [ ['require' => '/slug', 'optional' => 123] ],
            'optional-failure' => [ ['require' => 'slug', 'optional' => 'slug'] ],
        ];
    }

    /**
     * @dataProvider invalidUriProvider
     * @param array $parameters
     */
    public function testPathThrowExceptionForInvalidRouteParameter(array $parameters): void
    {
        $this->collection->set($this->page);
        $this->expectException(InvalidRouteParameterException::class);
        $this->collection->path('page', $parameters);
    }

    /**
     * @dataProvider invalidUriProvider
     * @param array $parameters
     */
    public function testUrlThrowExceptionForInvalidRouteParameter(array $parameters): void
    {
        $this->collection->set($this->page);
        $this->expectException(InvalidRouteParameterException::class);
        $this->collection->url('page', $parameters);
    }
}
