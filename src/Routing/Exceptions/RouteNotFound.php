<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing\Exceptions;

use function sprintf;

class RouteNotFound extends RoutingViolation
{
    public static function withMessageName(string $messageName): self
    {
        return new self(sprintf(
            'Route not found with message name %s', $messageName)
        );
    }
}
