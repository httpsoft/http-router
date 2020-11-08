# HTTP Router

[![License](https://poser.pugx.org/httpsoft/http-router/license)](https://packagist.org/packages/httpsoft/http-router)
[![Latest Stable Version](https://poser.pugx.org/httpsoft/http-router/v)](https://packagist.org/packages/httpsoft/http-router)
[![Total Downloads](https://poser.pugx.org/httpsoft/http-router/downloads)](https://packagist.org/packages/httpsoft/http-router)
[![GitHub Build Status](https://github.com/httpsoft/http-router/workflows/build/badge.svg)](https://github.com/httpsoft/http-router/actions)
[![GitHub Static Analysis Status](https://github.com/httpsoft/http-router/workflows/static/badge.svg)](https://github.com/httpsoft/http-router/actions)
[![Scrutinizer Code Coverage](https://scrutinizer-ci.com/g/httpsoft/http-router/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/httpsoft/http-router/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/httpsoft/http-router/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/httpsoft/http-router/?branch=master)

This package provides convenient management of HTTP request routing with support for [PSR-7](https://github.com/php-fig/http-message) and [PSR-15](https://github.com/php-fig/http-factory).

## Documentation

* [In English language](https://httpsoft.org/docs/router).
* [In Russian language](https://httpsoft.org/ru/docs/router).

## Installation

This package requires PHP version 7.4 or later.

```
composer require httpsoft/http-router
```

## Usage

```php
use HttpSoft\Router\RouteCollector;

/**
 * @var mixed $handler
 */

$router = new RouteCollector();

// Defining routes.
$router->get('home', '/', $handler);
$router->post('logout', '/logout', $handler);
$router->add('login', '/login', $handler, ['GET', 'POST']);

// Custom regular expressions for placeholder parameter tokens.
$router->delete('post.delete', '/post/delete/{id}', $handler)->tokens(['id' => '\d+']);

// Generate path '/post/delete/25'
$router->routes()->path('post.delete', ['id' => 25]);
// Generate url '//example.com/post/delete/25'
$router->routes()->url('post.delete', ['id' => 25], 'example.com');
// Generate url 'https://example.com/post/delete/25'
$router->routes()->url('post.delete', ['id' => 25], 'example.com', true);
```

Set the parameter to the default value.

```php
$router->get('post.view', '/post/{slug}{format}', $handler)
    ->tokens(['slug' => '[\w\-]+', 'format' => '\.[a-zA-z]{3,}'])
    ->defaults(['format' => '.html'])
;

// Generate path '/post/post-slug.html'.
$router->routes()->path('post.view', ['slug' => 'post-slug']);
```

Tokens of the route enclosed in `[...]` are considered optional.

```php
$router->get('post.list', '/posts{[page]}', $handler)
    ->tokens(['page' => '\d+'])
;

// '/posts/33'
$router->routes()->path('post.list', ['page' => 33]);
// '/posts'
$router->routes()->path('post.list');
```

If necessary, you can specify a specific host for route matching.

```php
// Only for example.com
$router->get('page', '/page', $handler)
    ->host('example.com')
;

// Only for subdomain.example.com
$router->get('page', '/page', $handler)
    ->host('subdomain.example.com')
;

// Only for shop.example.com or blog.example.com
$router->get('page', '/page', $handler)
    ->host('(shop|blog).example.com')
;
```

You can specify routes inside of a group.

```php
$router->group('/post', static function (RouteCollector $router): void {
    // '/post/post-slug'
    $router->get('post.view', '/{slug}', ViewHandler::class)->tokens(['slug' => '[\w-]+']);
    // '/post' or '/post/2'
    $router->get('post.list', '/list{[page]}', ListHandler::class)->tokens(['page' => '\d+']);
});

// The result will be equivalent to:

$router->get('post.view', '/post/{slug}', ViewHandler::class)->tokens(['slug' => '[\w-]+']);
$router->get('post.list', '/post/list{[page]}', ListHandler::class)->tokens(['page' => '\d+']);
```

Check matching routes.

```php
/**
 * @var mixed $handler
 * @var Psr\Http\Message\UriInterface $uri
 * @var Psr\Http\Message\ServerRequestInterface $request
 */

$router->get('page', '/page/{id}', $handler)->tokens(['id' => '\d+']);

// Match
$route = $router->routes()->match($request->withUri($uri->withPath('/page/11')));
$route->getMatchedParameters(); // ['id' => '11']

// Mismatch
$router->routes()->match($request->withUri($uri->withPath('/page/slug'))); // null
```
