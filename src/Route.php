<?php

declare(strict_types=1);

namespace HttpSoft\Router;

use HttpSoft\Router\Exception\InvalidRouteParameterException;
use Psr\Http\Message\ServerRequestInterface;

use function array_filter;
use function explode;
use function in_array;
use function is_scalar;
use function is_string;
use function preg_match;
use function preg_replace_callback;
use function rawurldecode;
use function strtoupper;
use function trim;

final class Route
{
    /**
     * The regexp pattern for placeholder (parameter name).
     */
    private const PLACEHOLDER = '~(?:\{([a-zA-Z_][a-zA-Z0-9_-]*|\[[\/a-zA-Z_][\/a-zA-Z0-9_-]*\])\})~';

    /**
     * The default regexp pattern for parameter token.
     */
    private const DEFAULT_TOKEN = '[^/]+';

    /**
     * @var string unique route name.
     */
    private string $name;

    /**
     * @var string pattern of the parameters.
     */
    private string $pattern;

    /**
     * @var mixed action, controller, callable, closure, etc.
     */
    private $handler;

    /**
     * @var string[] allowed request methods of the route.
     */
    private array $methods = [];

    /**
     * @var array<string, string|null> parameter names and regexp tokens.
     */
    private array $tokens = [];

    /**
     * @var array<string, string> parameter names and default parameter values.
     */
    private array $defaults = [];

    /**
     * @var array<string, string> matched parameter names and matched parameter values.
     */
    private array $matchedParameters = [];

    /**
     * @param string $name the unique route name.
     * @param string $pattern the pattern of the parameters.
     * @param mixed $handler the action, controller, callable, closure, etc.
     * @param array $methods allowed request methods of the route.
     * @psalm-suppress MixedAssignment
     */
    public function __construct(string $name, string $pattern, $handler, array $methods = [])
    {
        $this->name = $name;
        $this->pattern = $pattern;
        $this->handler = $handler;

        foreach ($methods as $method) {
            if (!is_string($method)) {
                throw InvalidRouteParameterException::forMethods($method);
            }

            $this->methods[] = strtoupper($method);
        }
    }

    /**
     * Gets the unique route name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the pattern of the parameters.
     *
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Gets the route handler.
     *
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Gets the allowed request methods of the route.
     *
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Gets the parameter tokens, as `parameter names` => `regexp tokens`.
     *
     * @return array<string, string|null>
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    /**
     * Gets the default parameter values, as `parameter names` => `default values`.
     *
     * @return array<string, string>
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Gets the matched parameters as `parameter names` => `parameter values`.
     *
     * The matched parameters appear after successful execution of the `match()` method.
     *
     * @return array<string, string>
     * @see match()
     */
    public function getMatchedParameters(): array
    {
        return $this->matchedParameters;
    }

    /**
     * Adds the parameter tokens.
     *
     * @param array<string, mixed> $tokens `parameter names` => `regexp tokens`
     * @return self
     * @throws InvalidRouteParameterException if the parameter token is not scalar or null.
     * @psalm-suppress MixedAssignment
     */
    public function tokens(array $tokens): self
    {
        foreach ($tokens as $key => $token) {
            if ($token === null) {
                $this->tokens[$key] = null;
                continue;
            }

            if (!is_string($token) || $token === '') {
                throw InvalidRouteParameterException::forTokens($token);
            }

            $this->tokens[$key] = $token;
        }

        return $this;
    }

    /**
     * Adds the default parameter values.
     *
     * @param array<string, mixed> $defaults `parameter names` => `default values`
     * @return self
     * @throws InvalidRouteParameterException if the default parameter value is not scalar.
     * @psalm-suppress MixedAssignment
     */
    public function defaults(array $defaults): self
    {
        foreach ($defaults as $key => $default) {
            if (!is_scalar($default)) {
                throw InvalidRouteParameterException::forDefaults($default);
            }

            $this->defaults[$key] = (string) $default;
        }

        return $this;
    }

    /**
     * Matches the request URI to route parameters.
     *
     * @param ServerRequestInterface $request
     * @return bool whether the route matches the request URI.
     * @psalm-suppress MixedArgument
     * @psalm-suppress MixedAssignment
     */
    public function match(ServerRequestInterface $request): bool
    {
        $pattern = (string) preg_replace_callback(self::PLACEHOLDER, function (array $matches): string {
            $parameter = $matches[1];

            return ($this->isOptionalParameter($parameter))
                ? $this->getPatternOptionalParametersReplacement($parameter)
                : $this->getPatternParameterReplacement($parameter)
            ;
        }, $this->pattern);

        if (preg_match('~^' . $pattern . '$~i', rawurldecode($request->getUri()->getPath()), $matches)) {
            foreach ($matches as $key => $parameter) {
                if (is_string($key)) {
                    $this->matchedParameters[$key] = $parameter;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Generates the URL path from the route parameters.
     *
     * @param array $parameters parameter-value set.
     * @return string URL path generated.
     * @throws InvalidRouteParameterException if parameter value does not match its regexp or require parameter is null.
     * @psalm-suppress MixedArgument
     * @psalm-suppress MixedAssignment
     */
    public function generate(array $parameters = []): string
    {
        return (string) preg_replace_callback(self::PLACEHOLDER, function (array $matches) use ($parameters): string {
            $parameter = $matches[1];

            if (!$this->isOptionalParameter($parameter)) {
                $pattern = $this->tokens[$parameter] ?? self::DEFAULT_TOKEN;
                $value = $parameters[$parameter] ?? $this->defaults[$parameter] ?? null;
                return $this->normalizeParameter($value, $parameter, $pattern, false);
            }

            $params = '';

            foreach ($this->parsePatternOptionalParameters($parameter) as $param) {
                $pattern = $this->tokens[$param] ?? self::DEFAULT_TOKEN;
                $value = $parameters[$param] ?? $this->defaults[$param] ?? null;

                if (($normalizeParameter = $this->normalizeParameter($value, $param, $pattern, true)) !== '') {
                    $params .= '/' . $normalizeParameter;
                }
            }

            return $params;
        }, $this->pattern);
    }

    /**
     * Checks whether the request method is allowed for the current route.
     *
     * @param string $method
     * @return bool
     */
    public function isAllowedMethod(string $method): bool
    {
        return ($this->methods === [] || in_array(strtoupper($method), $this->methods, true));
    }

    /**
     * Gets the replacement for required parameter in the regexp.
     *
     * @param string $parameter
     * @return string
     */
    private function getPatternParameterReplacement(string $parameter): string
    {
        return '(?P<' . $parameter . '>' . ($this->tokens[$parameter] ?? self::DEFAULT_TOKEN) . ')';
    }

    /**
     * Gets the replacement for optional parameters in the regexp.
     *
     * @param string $parameters
     * @return string
     */
    private function getPatternOptionalParametersReplacement(string $parameters): string
    {
        $head = $tail = '';

        foreach ($this->parsePatternOptionalParameters($parameters) as $parameter) {
            $head .= '(?:/' . $this->getPatternParameterReplacement($parameter);
            $tail .= ')?';
        }

        return $head . $tail;
    }

    /**
     * Parses the optional parameters pattern.
     *
     * @param string $parameters
     * @return string[]
     */
    private function parsePatternOptionalParameters(string $parameters): array
    {
        return array_filter(explode('/', trim($parameters, '[]')));
    }

    /**
     * Validates, normalizes and gets the parameter value.
     *
     * @param mixed $value
     * @param string $name
     * @param string $pattern
     * @param bool $optional
     * @return string
     * @throws InvalidRouteParameterException if parameter value does not match its regexp or require parameter is null.
     */
    private function normalizeParameter($value, string $name, string $pattern, bool $optional): string
    {
        if ($value === null) {
            if ($optional) {
                return '';
            }

            throw InvalidRouteParameterException::forNotPassed($name);
        }

        if (!is_scalar($value)) {
            throw InvalidRouteParameterException::forNotNullOrScalar($value);
        }

        $value = (string) $value;

        if (!preg_match('~^' . $pattern . '$~', $value)) {
            throw InvalidRouteParameterException::forNotMatched($name, $value, $pattern);
        }

        return $value;
    }

    /**
     * Checks whether the parameter is optional.
     *
     * @param string $parameter
     * @return bool
     */
    private function isOptionalParameter(string $parameter): bool
    {
        return $parameter[0] === '[';
    }
}
