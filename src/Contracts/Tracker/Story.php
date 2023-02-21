<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

use Throwable;

interface Story
{
    public function withEvent(string $event): void;

    public function currentEvent(): string;

    public function stop(bool $stopPropagation): void;

    public function isStopped(): bool;

    public function withRaisedException(Throwable $exception): void;

    public function exception(): ?Throwable;

    public function resetException(): void;

    public function hasException(): bool;
}
