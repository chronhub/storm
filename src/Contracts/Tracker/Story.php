<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

use Throwable;

interface Story
{
    /**
     * Set tracker event name
     *
     * @param  string  $event
     * @return void
     */
    public function withEvent(string $event): void;

    /**
     * Get tracker event name
     *
     * @return string
     */
    public function currentEvent(): string;

    /**
     * Stop propagation of tracker
     *
     * @param  bool  $stopPropagation
     * @return void
     */
    public function stop(bool $stopPropagation): void;

    /**
     * Check if propagation is stopped
     *
     * @return bool
     */
    public function isStopped(): bool;

    /**
     * Set exception on story
     *
     * @param  Throwable  $exception
     * @return void
     */
    public function withRaisedException(Throwable $exception): void;

    /**
     * Get exception if exists
     *
     * @return Throwable|null
     */
    public function exception(): ?Throwable;

    /**
     * Remove exception from story
     */
    public function resetException(): void;

    /**
     * Check if exception exists on story
     *
     * @return bool
     */
    public function hasException(): bool;
}
