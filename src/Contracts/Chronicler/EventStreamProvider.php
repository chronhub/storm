<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface EventStreamProvider
{
    /**
     * Create new stream
     */
    public function createStream(string $streamName, ?string $tableName, ?string $category = null): bool;

    /**
     * Delete stream by name
     */
    public function deleteStream(string $streamName): bool;

    /**
     * @return array{string}
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
     * Filter streams without internal streams which start with dollar sign "$"
     *
     * @return array<string>
     */
    public function allWithoutInternal(): array;

    public function hasRealStreamName(string $streamName): bool;
}
