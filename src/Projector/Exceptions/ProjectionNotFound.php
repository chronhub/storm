<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Exceptions;

final class ProjectionNotFound extends RuntimeException
{
    public static function withName(string $name): self
    {
        return new self("Projection $name not found");
    }
}
