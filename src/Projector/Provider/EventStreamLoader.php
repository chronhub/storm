<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Provider;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Illuminate\Support\Collection;

use function array_unique;
use function count;

class EventStreamLoader
{
    final public const ALL = 'all';

    final public const CATEGORIES = 'categories';

    final public const NAMES = 'names';

    public function __construct(protected readonly EventStreamProvider $eventStreamProvider)
    {
    }

    /**
     * Load stream names from one query type
     * Note that projection will run blank if event streams are empty
     *
     * @param  array{'all'?: bool, 'categories'?: string[], 'names'?: string[]} $queries
     * @return Collection<string>
     *
     * @throws RuntimeException when local stream names/categories are empty or not unique
     */
    public function loadFrom(array $queries): Collection
    {
        return collect($queries)->flatMap(fn (bool|array $streams, string $type) => $this->matchQuery($type, $streams));
    }

    private function matchQuery(string $type, array|bool $streams): array
    {
        if ($type === self::ALL) {
            return $this->eventStreamProvider->allWithoutInternal();
        }

        $this->assertQueriesNotEmpty($type, $streams);
        $this->assertUniqueStreamNames($type, $streams);

        return match ($type) {
            self::CATEGORIES => $this->eventStreamProvider->filterByAscendantCategories($streams),
            self::NAMES => $this->eventStreamProvider->filterByAscendantStreams($streams),
            default => throw new RuntimeException("Unknown query $type"),
        };
    }

    private function assertQueriesNotEmpty(string $type, array $streams): void
    {
        if ($streams === []) {
            throw new RuntimeException("Stream $type cannot be empty");
        }
    }

    private function assertUniqueStreamNames(string $key, array $streams): void
    {
        if (count($streams) !== count(array_unique($streams))) {
            throw new RuntimeException("Stream $key must be unique");
        }
    }
}
