<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Illuminate\Support\Collection;

use function key;

class EventStreamLoader
{
    public function __construct(protected readonly EventStreamProvider $eventStreamProvider)
    {
    }

    /**
     * @param  array{'all'?: bool, 'categories'?: string[], 'names'?: string[]} $queries
     * @return Collection<array<non-empty-string>>
     *
     * @throws InvalidArgumentException when local or remote stream names is empty or not unique
     */
    public function loadFrom(array $queries): Collection
    {
        return tap($this->matchQuery($queries), function (Collection $streams) {
            if ($streams->isEmpty()) {
                throw new InvalidArgumentException('No stream set or found');
            }

            if ($streams->unique()->count() !== $streams->count()) {
                throw new InvalidArgumentException('Duplicate stream names is not allowed');
            }
        });
    }

    private function matchQuery(array $queries): Collection
    {
        $streams = match (key($queries)) {
            'all' => $this->eventStreamProvider->allWithoutInternal(),
            'categories' => $this->eventStreamProvider->filterByAscendantCategories($queries['categories']),
            default => $queries['names'] ?? [],
        };

        return collect($streams);
    }
}
