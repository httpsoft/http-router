<?php

declare(strict_types=1);

namespace HttpSoft\Router\Exception;

use InvalidArgumentException;

use function gettype;
use function get_class;
use function is_object;
use function sprintf;

class InvalidRouteParameterException extends InvalidArgumentException
{
    /**
     * @param mixed $method
     * @return self
     */
    public static function forMethods($method): self
    {
        return self::forType($method, 'The request methods MUST be a string type, %s received.');
    }

    /**
     * @param mixed $token
     * @return self
     */
    public static function forTokens($token): self
    {
        return self::forType($token, 'Parameter token values MUST be null or non-empty string, %s received.');
    }

    /**
     * @param mixed $default
     * @return self
     */
    public static function forDefaults($default): self
    {
        return self::forType(
            $default,
            'The default parameter values MUST be a scalar type (string, integer, float, boolean), %s received.'
        );
    }

    /**
     * @param mixed $parameter
     * @return self
     */
    public static function forNotNullOrScalar($parameter): self
    {
        return self::forType(
            $parameter,
            'The parameter values MUST be a null or scalar type (string, integer, float, boolean), %s received.'
        );
    }

    /**
     * @param string $name
     * @return self
     */
    public static function forNotPassed(string $name): self
    {
        return new self(sprintf('The value of the required parameter "%s" is not passed or is null.', $name));
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $pattern
     * @return self
     */
    public static function forNotMatched(string $name, string $value, string $pattern): self
    {
        return new self(sprintf(
            'The value "%s" of the "%s" parameter does not match the regexp `%s`.',
            $value,
            $name,
            $pattern
        ));
    }

    /**
     * @param mixed $parameter
     * @param string $message
     * @return self
     */
    private static function forType($parameter, string $message): self
    {
        return new self(sprintf($message, (is_object($parameter) ? get_class($parameter) : gettype($parameter))));
    }
}
