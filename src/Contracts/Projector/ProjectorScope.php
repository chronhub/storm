<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Reporter\DomainEvent;

interface ProjectorScope
{
    /**
     * Acknowledge the event stream only once.
     * Otherwise, it will return null.
     */
    public function ack(string $event): ?static;

    /**
     * Acknowledge one of the events.
     *
     * The purpose of this method is to handle stream events
     * in the same manner as e.g., updating the status of a read model.
     *
     * You can only acknowledge once and all operations
     * must be done inside the method.
     * Otherwise, it will return null.
     */
    public function ackOneOf(string ...$events): ?static;

    /**
     * Check if the stream event given match the current processed event.
     */
    public function isOf(string $event): bool;

    /**
     * Increment the state field
     */
    public function incrementState(string $field = 'count', int $value = 1): static;

    /**
     * Update or increment the state field
     */
    public function updateState(string $field = 'count', int|string $value = 1, bool $increment = false): static;

    /**
     * Merge the state field with the given value
     */
    public function mergeState(string $field, mixed $value): static;

    /**
     * Conditional
     */
    public function when(bool $condition, null|callable|array $callback = null, null|callable|array $fallback = null): ?static;

    /**
     * Stop the projection on a truthy condition
     */
    public function stopWhen(bool $condition): static;

    /**
     * Match the condition
     */
    public function match(bool $condition): ?static;

    /**
     * Get the processed event stream only if it has been acknowledged.
     *
     * @throws RuntimeException if event stream is not acknowledged
     */
    public function event(): DomainEvent;

    /**
     * Get the current state if it has been initialized.
     */
    public function getState(): ?array;

    /**
     * Check if the event stream has been acknowledged.
     */
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
