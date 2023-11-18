<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Chronhub\Storm\Stream\StreamName;

interface EventStreamProvider
{
    final public const INTERNAL_STREAM_PREFIX = '$';

    /**
     * Create new stream
     *
     * @param non-empty-string      $streamName
     * @param non-empty-string|null $category
     */
    public function createStream(string $streamName, ?string $streamTable, string $category = null): bool;

    /**
     * Delete stream by name
     *
     * @param non-empty-string $streamName
     */
    public function deleteStream(string $streamName): bool;

    /**
     * @param  array<string|StreamName> $streamNames
     * @return array<string>
     */
    public function filterByAscendantStreams(array $streamNames): array;

    /**
     * Filter categories by names
     *
     * @param  array<string> $categoryNames
     * @return array<string>
     */
    public function filterByAscendantCategories(array $categoryNames): array;

    /**
     * Filter streams without internal streams which start with INTERNAL_STREAM_PREFIX
     *
     * @return array<string>
     */
    public function allWithoutInternal(): array;

    /**
     * Check if real stream name exists
     *
     * @param non-empty-string $streamName
     */
    public function hasRealStreamName(string $streamName): bool;
}
