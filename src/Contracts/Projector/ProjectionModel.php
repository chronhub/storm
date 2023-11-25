<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectionModel
{
    public function name(): string;

    public function positions(): string;

    public function state(): string;

    public function status(): string;

    public function lockedUntil(): ?string;
}
