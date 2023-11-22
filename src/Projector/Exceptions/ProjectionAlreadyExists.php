<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Exceptions;

class ProjectionAlreadyExists extends RuntimeException
{
    public static function withName(string $name): self
    {
        return new self("Projection with name $name already exists");
    }
}
