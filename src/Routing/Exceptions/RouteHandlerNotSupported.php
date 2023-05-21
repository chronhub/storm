<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing\Exceptions;

final class RouteHandlerNotSupported extends RoutingViolation
{
    public static function withMessageName(string $messageName): self
    {
        return new self("Route handler is not supported for message name $messageName");
    }
}
