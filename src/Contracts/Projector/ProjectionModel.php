<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectionModel
{
    /**
     * Get projection name
     */
    public function name(): string;

    /**
     * Get projection position
     */
    public function position(): string;

    /**
     * Get projection state
     */
    public function state(): string;

    /**
     * Get projection status
     */
    public function status(): string;

    /**
     * Get projection locked
     */
    public function lockedUntil(): ?string;
}
