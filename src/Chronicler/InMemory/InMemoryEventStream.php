<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler\InMemory;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Stream\StreamName;
use Illuminate\Support\Collection;

use function in_array;
use function is_null;
use function is_string;
use function str_starts_with;

final readonly class InMemoryEventStream implements EventStreamProvider
{
    /**
     * @var Collection{string, string|null}
     */
    private Collection $eventStreams;

    public function __construct()
    {
        $this->eventStreams = new Collection();
    }

    public function createStream(string $streamName, ?string $streamTable, string $category = null): bool
    {
        if ($this->eventStreams->has($streamName)) {
            return false;
        }

        $this->eventStreams->put($streamName, $category);

        return true;
    }

    public function deleteStream(string $streamName): bool
    {
        if (! $this->eventStreams->has($streamName)) {
            return false;
        }

        $this->eventStreams->forget($streamName);

        return true;
    }

    public function filterByAscendantStreams(array $streamNames): array
    {
        foreach ($streamNames as &$streamName) {
            if ($streamName instanceof StreamName) {
                $streamName = $streamName->name;
            }
        }

        return $this->eventStreams->filter(
            static fn (?string $category, string $streamName) => is_null($category) && in_array($streamName, $streamNames, true)
        )->sortKeys()->keys()->toArray();
    }

    public function filterByAscendantCategories(array $categoryNames): array
    {
        return $this->eventStreams->filter(
            static fn (?string $category) => is_string($category) && in_array($category, $categoryNames, true)
        )->sortKeys()->keys()->toArray();
    }

    public function allWithoutInternal(): array
    {
        return $this->eventStreams->filter(
            static fn (?string $category, string $streamName) => ! str_starts_with($streamName, self::INTERNAL_STREAM_PREFIX)
        )->keys()->toArray();
    }

    public function hasRealStreamName(string $streamName): bool
    {
        return $this->eventStreams->has($streamName);
    }
}
