<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Exceptions;

use function sprintf;

final class ProjectionNotFound extends RuntimeException
{
    public static function withName(string $name): self
    {
        return new self(sprintf('Projection %s not found', $name));
    }
}
