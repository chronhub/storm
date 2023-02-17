<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

use Throwable;

interface Story
{
    /**
     * Set tracker event name
     */
    public function withEvent(string $event): void;

    /**
     * Get tracker event name
     */
    public function currentEvent(): string;

    /**
     * Stop propagation of tracker
     */
    public function stop(bool $stopPropagation): void;

    /**
     * Check if propagation is stopped
     */
    public function isStopped(): bool;

    /**
     * Set exception on story
     */
    public function withRaisedException(Throwable $exception): void;

    /**
     * Get exception if exists
     */
    public function exception(): ?Throwable;

    /**
     * Remove exception from story
     */
    public function resetException(): void;

    /**
     * Check if exception exists on story
     */
    public function hasException(): bool;
}
