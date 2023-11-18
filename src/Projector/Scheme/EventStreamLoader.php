<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Illuminate\Support\Collection;

use function array_unique;
use function key;

final readonly class EventStreamLoader
{
    public function __construct(private EventStreamProvider $eventStreamProvider)
    {
    }

    /**
     * @param  array{all?: bool, categories?: string[], names?: string[]} $queries
     * @return Collection<string>
     *
     * @throws InvalidArgumentException when stream names is empty or not unique
     */
    public function loadFrom(array $queries): Collection
    {
        $streams = match (key($queries)) {
            'all' => $this->eventStreamProvider->allWithoutInternal(),
            'categories' => $this->eventStreamProvider->filterByAscendantCategories($queries['categories']),
            default => $this->handleStreamNames($queries['names'] ?? []),
        };

        return new Collection($streams);
    }

    private function handleStreamNames(array $streamNames): array
    {
        if ($streamNames === []) {
            throw new InvalidArgumentException('Stream names can not be empty');
        }

        // checkMe for duplicate stream names as it could be redundant with provider
        // which already check for duplicate stream names
        $uniqueStreamNames = array_unique($streamNames);

        if ($uniqueStreamNames !== $streamNames) {
            throw new InvalidArgumentException('Duplicate stream names is not allowed');
        }

        return $uniqueStreamNames;
    }
}
