<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use DateTimeImmutable;
use JsonSerializable;

/**
 * @template TStream of array<non-empty-string,int<0,max>>
 */
interface StreamManagerInterface extends JsonSerializable
{
    /**
     * Watches event streams based on given queries.
     *
     * @param array{all?: bool, categories?: string[], names?: string[]} $queries
     */
    public function watchStreams(array $queries): void;

    /**
     * Merges remote/local stream positions.
     *
     * @param TStream $streamsPositions
     */
    public function syncStreams(array $streamsPositions): void;

    /**
     * Binds a stream name to the next available position.
     *
     * @param int<1, max> $expectedPosition The incremented position of the current event.
     *
     * Successful bind in order:
     *      - event time is false ( meant for query projection )
     *      - no retry set
     *      - no gap detected
     *      - gap detected but no more retries available
     *      - successful detection window checked
     *
     * @throw RuntimeException When stream name is not watched
     */
    public function bind(string $streamName, int $expectedPosition, DateTimeImmutable|string|false $eventTime): bool;

    /**
     * Sleeps for the specified retry duration available.
     *
     * @throws RuntimeException When no gap is detected
     * @throws RuntimeException When no more retries are available.
     */
    public function sleep(): void;

    /**
     * Check if a gap is detected.
     */
    public function hasGap(): bool;

    /**
     * Check if there is still retries available.
     */
    public function hasRetry(): bool;

    /**
     * Returns the current number of retries.
     *
     * @return int<0, max>
     */
    public function retries(): int;

    /**
     * Resets stream manager: positions, gap and retries.
     */
    public function resets(): void;

    /**
     * Returns the current stream positions.
     */
    public function all(): array;
}
