<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing\Exceptions;

class RouteNotFound extends RoutingViolation
{
    public static function withMessageName(string $messageName): self
    {
        return new self("Route not found with message name $messageName");
    }
}
