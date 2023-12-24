<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Reporter\DomainEvent;

interface ProjectorScope
{
    /**
     * Acknowledge the event.
     */
    public function ack(string $event): ?static;

    /**
     * Acknowledge one of the events.
     */
    public function ackOneOf(string ...$events): ?static;

    public function isOf(string $event): bool;

    public function incrementState(string $field = 'count', int $value = 1): static;

    public function updateState(string $field = 'count', int|string $value = 1, bool $increment = false): static;

    public function mergeState(string $field, mixed $value): static;

    public function when(bool $condition, null|callable|array $callback = null, null|callable|array $fallback = null): ?static;

    public function stopWhen(bool $condition): static;

    public function match(bool $condition): ?static;

    public function event(): DomainEvent;

    public function getState(): ?array;

    public function isAcked(): bool;

    /**
     * Stop the projection.
     */
    public function stop(): void;

    /**
     * Return the current stream name
     */
    public function streamName(): string;

    /**
     * Return the clock implementation.
     */
    public function clock(): SystemClock;
}
