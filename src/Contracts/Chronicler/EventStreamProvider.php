<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface EventStreamProvider
{
    /**
     * Create new stream
     *
     * @param  string  $streamName
     * @param  string|null  $tableName
     * @param  string|null  $category
     * @return bool
     */
    public function createStream(string $streamName, ?string $tableName, ?string $category = null): bool;

    /**
     * Delete stream by name
     *
     * @param  string  $streamName
     * @return bool
     */
    public function deleteStream(string $streamName): bool;

    /**
     * Filter stream by names
     *
     * @param  array  $streamNames
     * @return array
     */
    public function filterByStreams(array $streamNames): array;

    /**
     * Filter categories by names
     *
     * @param  array<string>  $categoryNames
     * @return array<string>
     */
    public function filterByCategories(array $categoryNames): array;

    /**
     * Filter streams without internal streams
     * which start with dollar sign "$"
     *
     * @return array<string>
     */
    public function allWithoutInternal(): array;

    /**
     * Check if the real stream name exists
     *
     * @param  string  $streamName
     * @return bool
     */
    public function hasRealStreamName(string $streamName): bool;
}
