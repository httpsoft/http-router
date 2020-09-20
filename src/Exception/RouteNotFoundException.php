<?php

declare(strict_types=1);

namespace HttpSoft\Router\Exception;

use RuntimeException;

use function sprintf;

class RouteNotFoundException extends RuntimeException
{
    /**
     * @param string $routeName
     * @return self
     */
    public static function create(string $routeName): self
    {
        return new self(sprintf(
            'The route "%s" was not found.',
            $routeName,
        ));
    }
}
