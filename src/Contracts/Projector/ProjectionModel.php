<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectionModel
{
    /**
     * Get projection name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get projection position
     *
     * @return string
     */
    public function position(): string;

    /**
     * Get projection state
     *
     * @return string
     */
    public function state(): string;

    /**
     * Get projection status
     *
     * @return string
     */
    public function status(): string;

    /**
     * Get projection locked
     *
     * @return string|null
     */
    public function lockedUntil(): ?string;
}
