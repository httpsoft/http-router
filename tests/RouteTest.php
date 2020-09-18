<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Router;

use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\Uri;
use HttpSoft\Router\Exception\InvalidRouteParameterException;
use HttpSoft\Router\Route;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use StdClass;

use function array_map;
use function is_array;
use function strtoupper;

class RouteTest extends TestCase
{
    /**
     * @var callable
     */
    private $handler;

    /**
     * @var ServerRequest
     */
    private ServerRequest $request;

    public function setUp(): void
    {
        $this->handler = fn(): ResponseInterface => new Response();
        $this->request = new ServerRequest();
    }

    public function testGettersDefault(): void
    {
        $route = new Route($name = 'home', $pattern = '/', $this->handler);
        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());
        $this->assertSame([], $route->getMethods());
        $this->assertSame([], $route->getTokens());
        $this->assertSame([], $route->getDefaults());
        $this->assertSame([], $route->getMatchedParameters());
    }

    public function testGettersWithParametersPassedToConstructorAndSetters(): void
    {
        $route = new Route($name = 'blog.view', $pattern = '/blog/{slug}{format}', $this->handler, $methods = ['GET']);
        $route
            ->tokens($tokens = ['slug' => '[\w\-]+', 'format' => '\.[a-zA-z]{3,}'])
            ->defaults($defaults = ['format' => '.html'])
        ;
        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());
        $this->assertSame($methods, $route->getMethods());
        $this->assertSame($tokens, $route->getTokens());
        $this->assertSame($defaults, $route->getDefaults());
        $this->assertSame([], $route->getMatchedParameters());
    }

    /**
     * @return array
     */
    public function methodProvider(): array
    {
        return [
            'GET' => ['GET'],
            'Get' => ['Get'],
            'get' => ['get'],
            'custom' => ['CustomMethod'],
            'many' => [['GET', 'Post', 'pUt', 'patch', 'heaD']],
        ];
    }

    /**
     * @dataProvider methodProvider
     * @param string|array $method
     */
    public function testConstructValidMethod($method): void
    {
        $methods = is_array($method) ? $method : [$method];
        $route = new Route($name = 'blog', $pattern = '/blog', $this->handler, $methods);
        $normalizeMethods = array_map(static fn(string $method): string => strtoupper($method), $methods);
        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());
        $this->assertSame($normalizeMethods, $route->getMethods());
    }

    /**
     * @return array
     */
    public function invalidMethodProvider(): array
    {
        return [
            'null' => [null],
            'false' => [false],
            'true' => [true],
            'integer' => [1],
            'float' => [1.1],
            'array' => [['GET']],
            'empty-array' => [[]],
            'object' => [new StdClass()],
            'callable' => [fn() => null],
        ];
    }

    /**
     * @dataProvider invalidMethodProvider
     * @param mixed $method
     */
    public function testConstructorThrowExceptionForInvalidMethod($method): void
    {
        $this->expectException(InvalidRouteParameterException::class);
        new Route('blog', '/blog', $this->handler, [$method]);
    }

    /**
     * @return array
     */
    public function tokenProvider(): array
    {
        return [
            'integer' => ['\d+', '0'],
            'float' => ['[0-9]+\.[0-9]+', '0.1'],
            'string' => ['[\w\-]+', 'slug'],
            'static' => ['static', 'static'],
        ];
    }

    /**
     * @dataProvider tokenProvider
     * @param string $token
     * @param mixed $value
     */
    public function testTokens(string $token, $value): void
    {
        $route = (new Route($name = 'blog.view', $pattern = '/blog/{placeholder}', $this->handler))
            ->tokens($tokens = ['placeholder' => $token])
        ;
        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());
        $this->assertSame($tokens, $route->getTokens());
        $this->assertTrue($route->match($this->request->withUri(new Uri("/blog/{$value}"))));
    }

    /**
     * @return array
     */
    public function twoTokenProvider(): array
    {
        return [
            'java' => ['java', 0],
            'php' => ['php', 112],
            'python' => ['python', '21'],
        ];
    }

    /**
     * @dataProvider twoTokenProvider
     * @param string $topic
     * @param int|string $id
     */
    public function testTokensWithTwoParameters(string $topic, $id): void
    {
        $route = (new Route($name = 'blog.view', $pattern = '/blog/{topic}/{id}', $this->handler))
            ->tokens($tokens = ['topic' => '[\w\-]+', 'id' => '\d+'])
        ;
        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());
        $this->assertSame($tokens, $route->getTokens());
        $this->assertTrue($route->match($this->request->withUri(new Uri("/blog/{$topic}/{$id}"))));
    }

    /**
     * @return array
     */
    public function optionalParameterProvider(): array
    {
        return [
            'require' => ['require', true],
            'require-optional_1' => ['require/optional-1', true],
            'require-optional_1-integer' => ['require/optional-1', false, '\d+'],
            'require-optional_1-optional_2' => ['require/optional-1/optional-2', true],
            'require-optional_1-optional_2-integer' => ['require/optional-1/optional-2', false, '\d+'],
            'require-optional_1-optional_2-optional_3' => ['require/optional-1/optional-2/optional-3', false],
        ];
    }

    /**
     * @dataProvider optionalParameterProvider
     * @param string $path
     * @param bool $isSuccess
     * @param string|null $token
     */
    public function testTokensWithOptionalParameters(string $path, bool $isSuccess, string $token = null): void
    {
        $route = (new Route($name = 'test', $pattern = '/page/{require}{[optional_1/optional_2]}', $this->handler))
            ->tokens($tokens = ['require' => '[\w\-]+', 'optional_1' => $token])
        ;
        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());

        if ($isSuccess) {
            $this->assertTrue($route->match($this->request->withUri(new Uri("/page/{$path}"))));
        } else {
            $this->assertFalse($route->match($this->request->withUri(new Uri("/page/{$path}"))));
        }
    }

    /**
     * @return array
     */
    public function invalidTokenProvider(): array
    {
        return [
            'false' => [false],
            'true' => [true],
            'integer' => [1],
            'float' => [1.1],
            'array' => [['\d+']],
            'empty-array' => [[]],
            'empty-string' => [''],
            'object' => [new StdClass()],
            'callable' => [fn() => null],
        ];
    }

    /**
     * @dataProvider invalidTokenProvider
     * @param mixed $token
     */
    public function testTokensThrowExceptionForInvalidToken($token): void
    {
        $this->expectException(InvalidRouteParameterException::class);
        (new Route('blog.view', '/blog/{slug}', $this->handler))->tokens(['slug' => $token]);
    }

    /**
     * @return array
     */
    public function validDefaultProvider(): array
    {
        return [
            'integer' => ['0', 0],
            'float' => ['0.1', 0.1],
            'string' => ['default', 'default'],
            'true' => ['1', true],
        ];
    }

    /**
     * @dataProvider validDefaultProvider
     * @param string $expected
     * @param mixed $default
     */
    public function testDefaults(string $expected, $default): void
    {
        $route = (new Route($name = 'blog.view', $pattern = '/blog/{placeholder}', $this->handler))
            ->defaults($defaults = ['placeholder' => $default])
        ;
        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());
        $this->assertEquals($defaults, $route->getDefaults());
        $this->assertSame(array_map(fn($default): string => (string) $default, $defaults), $route->getDefaults());
        $this->assertTrue($route->match($this->request->withUri(new Uri("/blog/{$expected}"))));
    }

    /**
     * @return array
     */
    public function invalidDefaultProvider(): array
    {
        return [
            'null' => [null],
            'array' => [[1]],
            'empty-array' => [[]],
            'object' => [new StdClass()],
            'callable' => [fn() => null],
        ];
    }

    /**
     * @dataProvider invalidDefaultProvider
     * @param mixed $default
     */
    public function testDefaultsThrowExceptionForInvalidDefaultValue($default): void
    {
        $this->expectException(InvalidRouteParameterException::class);
        (new Route('blog.list', '/blog/page/{page}', $this->handler))->defaults(['page' => $default]);
    }

    public function testIsAllowedMethod(): void
    {
        $route = new Route('home', '/', $this->handler, ['GET', 'POST']);
        $this->assertTrue($route->isAllowedMethod('GET'));
        $this->assertTrue($route->isAllowedMethod('POST'));
        $this->assertFalse($route->isAllowedMethod('PUT'));
        $this->assertFalse($route->isAllowedMethod('PATCH'));
        $this->assertFalse($route->isAllowedMethod('DELETE'));
        $this->assertFalse($route->isAllowedMethod('HEAD'));
        $this->assertFalse($route->isAllowedMethod('OPTIONS'));
    }

    /**
     * @return array
     */
    public function matchProvider(): array
    {
        return [
            'static-all-success' => [
                ['token' => 'static', 'value' => 'static'],
                ['token' => 'static', 'value' => 'static'],
                true
            ],
            'static-require-only' => [
                ['token' => 'static', 'value' => 'static'],
                ['token' => null, 'value' => 'static'],
                true
            ],
            'static-require-value-failure' => [
                ['token' => 'static', 'value' => 'failure'],
                ['token' => 'static', 'value' => 'static'],
                false
            ],
            'slug-all-success' => [
                ['token' => '[\w\-]+', 'value' => 'category-slug'],
                ['token' => '[\w\-]+', 'value' => 'post-slug'],
                true
            ],
            'slug-require-without-token' => [
                ['value' => 'category-slug'],
                ['token' => '[\w\-]+', 'value' => '/post-slug'],
                false
            ],
            'slug-optional-value-failure' => [
                ['token' => '[\w\-]+', 'value' => 'category-slug'],
                ['token' => '[\w\-]+', 'value' => '/post-slug'],
                false
            ],
            'id-all-success' => [
                ['token' => '\d+', 'value' => 0],
                ['token' => '\d+', 'value' => '1'],
                true
            ],
            'id-require-value-failure' => [
                ['token' => '\d+', 'value' => 'slug'],
                ['token' => '\d+', 'value' => '1'],
                false
            ],
            'id-optional-value-passed-but-token-null' => [
                ['token' => '\d+', 'value' => 1],
                ['token' => null, 'value' => 'slug'],
                true
            ],
        ];
    }

    /**
     * @dataProvider matchProvider
     * @param array $require
     * @param array $optional
     * @param bool $isSuccess
     */
    public function testMatch(array $require, array $optional, bool $isSuccess): void
    {
        $matched = ['require' => $require['value'], 'optional' => $optional['value']];
        $route = (new Route($name = 'test', $pattern = "/page/{require}{[optional]}", $this->handler));
        $uri = new Uri("/page/{$require['value']}" . (isset($optional['value']) ? '/' . $optional['value'] : ''));

        if (isset($require['token'])) {
            $route->tokens(['require' => $require['token']]);
        }

        if (isset($optional['token'])) {
            $route->tokens(['optional' => $optional['token']]);
        }

        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());

        if ($isSuccess) {
            $this->assertTrue($route->match($this->request->withUri($uri)));
            $this->assertEquals($matched, $route->getMatchedParameters());
            $this->assertSame(array_map(fn($item): string => (string) $item, $matched), $route->getMatchedParameters());
        } else {
            $this->assertFalse($route->match($this->request->withUri($uri)));
        }
    }

    /**
     * @return array
     */
    public function generateProvider(): array
    {
        return [
            'home' => ['/', '/', []],
            'blog' => ['/blog', '/blog', []],
            'about' => ['/{page}', '/about', ['page' => 'about'], ['page' => '(about|contact|login)']],
            'logout' => ['/{page}', '/about', ['page' => 'logout'], ['page' => '(about|contact|login)'], [], false],
            'sitemap' => ['/sitemap{format}', '/sitemap.xml', ['format' => '.xml'], ['format' => '\.[a-zA-z]{3,}']],
            'blog-list' => ['/blog/page/{page}', '/blog/page/1', [], ['page' => '\d+'], ['page' => 1]],
            'blog-view' => ['/blog/{slug}', '/blog/post', ['slug' => 'post'], ['slug' => '[\w\-]+']],
            'blog-edit' => ['/blog/{id}', '/blog/11', ['id' => 11], ['id' => '\d+'], []],
            'path-require-without-tokens' => [
                '/path/{level-1}/{level-2}',
                '/path/to/target',
                ['level-1' => 'to'],
                [],
                ['level-2' => 'target']
            ],
            'path-require-with-tokens' => [
                '/path/{level-1}/{level-2}',
                '/path/to/target',
                ['level-1' => 'to', 'level-2' => 'target'],
                ['level-1' => '[\w\-]+', 'level-2' => '[\w\-]+'],
            ],
            'path-require-with-failure-token' => [
                '/path/{level-1}/{level-2}',
                '/path/to/target',
                ['level-1' => 'to', 'level-2' => 'target'],
                ['level-1' => '\d+', 'level-2' => 'target'],
                [],
                false
            ],
            'path-optional-without-params-tokens' => [
                '/path{[level-1/level-2]}',
                '/path',
                [],
            ],
            'path-optional-with-tokens' => [
                '/path{[level-1/level-2]}',
                '/path/to/target',
                ['level-1' => 'to', 'level-2' => 'target'],
                ['level-1' => '\d+', 'level-2' => 'target'],
                [],
                false
            ],
            'path-optional-with-params-without-tokens' => [
                '/path{[level-1/level-2]}',
                '/path/to/target',
                ['level-1' => 'to', 'level-2' => 'target'],
            ],
        ];
    }

    /**
     * @dataProvider generateProvider
     * @param string $pattern
     * @param string $uri
     * @param array $params
     * @param array $tokens
     * @param array $defaults
     * @param bool $isSuccess
     */
    public function testGenerate(
        string $pattern,
        string $uri,
        array $params,
        array $tokens = [],
        array $defaults = [],
        bool $isSuccess = true
    ): void {
        $uri = new Uri($uri);

        $route = (new Route($name = 'test', $pattern, $this->handler))
            ->tokens($tokens)
            ->defaults($defaults)
        ;

        $this->assertSame($name, $route->getName());
        $this->assertSame($pattern, $route->getPattern());
        $this->assertSame($this->handler, $route->getHandler());

        if (!$isSuccess) {
            $this->expectException(InvalidRouteParameterException::class);
        }

        $this->assertSame((string) $uri, $route->generate($params));
    }
}
