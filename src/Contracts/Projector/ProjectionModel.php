<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectionModel
{
    /**
     * Get the projection name.
     */
    public function name(): string;

    /**
     * Get the projection position as json representation.
     */
    public function checkpoint(): string;

    /**
     * Get the projection state as json representation.
     */
    public function state(): string;

    /**
     * Get the projection status.
     */
    public function status(): string;

    /**
     * Get the projection locked until datetime.
     */
    public function lockedUntil(): ?string;
}
