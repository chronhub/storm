<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing\Exceptions;

use function sprintf;

final class RouteHandlerNotSupported extends RoutingViolation
{
    public static function withMessageName(string $messageName): self
    {
        return new self(sprintf(
            'Route handler is not supported for message name %s', $messageName)
        );
    }
}
