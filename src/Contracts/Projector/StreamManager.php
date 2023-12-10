<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Reporter\DomainEvent;
use JsonSerializable;

interface StreamManager extends JsonSerializable
{
    /**
     * Watches event streams based on given queries.
     *
     * @param array{'all'?:bool, 'categories'?:array<non-empty-string>, 'names'?:array<non-empty-string>} $queries
     */
    public function discover(array $queries): void;

    /**
     * Merges local with remote stream positions.
     *
     * @param array<non-empty-string,int<0,max>> $streamsPositions
     */
    public function merge(array $streamsPositions): void;

    /**
     * Binds a stream name to the next available position.
     *
     * @param int<1,max> $expectedPosition The incremented position of the current event.
     *
     * @throw RuntimeException When stream name is not watched
     * @throw RuntimeException When event time is null for gap detection
     */
    public function bind(string $streamName, int $expectedPosition, DomainEvent $event): bool;

    /**
     * Check if the next position is available.
     *
     * @throw RuntimeException When stream name is not watched
     */
    public function isAvailable(string $streamName, int $expectedPosition): bool;

    /**
     * Check if stream name is watched.
     */
    public function hasStream(string $streamName): bool;

    /**
     * Returns the current stream positions.
     */
    public function all(): array;

    /**
     * Resets stream positions.
     */
    public function resets(): void;
}
