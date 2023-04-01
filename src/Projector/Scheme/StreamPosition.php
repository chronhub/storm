<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use JsonSerializable;
use Illuminate\Support\Collection;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use function key;

final class StreamPosition implements JsonSerializable
{
    /**
     * @var Collection<string, int>
     */
    private Collection $container;

    public function __construct(private readonly EventStreamProvider $eventStreamProvider)
    {
        $this->container = new Collection();
    }

    public function watch(array $queries): void
    {
        $container = new Collection();

        foreach ($this->loadStreamsFrom($queries) as $stream) {
            $container->put($stream, 0);
        }

        $this->container = $container->merge($this->container);
    }

    public function discover(array $streamsPositions): void
    {
        $this->container = $this->container->merge($streamsPositions);
    }

    public function bind(string $streamName, int $position): void
    {
        $this->container[$streamName] = $position;
    }

    public function reset(): void
    {
        $this->container = new Collection();
    }

    public function hasNextPosition(string $streamName, int $position): bool
    {
        return $this->container[$streamName] + 1 === $position;
    }

    public function all(): array
    {
        return $this->container->toArray();
    }

    public function jsonSerialize(): array
    {
        return $this->container->toArray();
    }

    protected function loadStreamsFrom(array $queries): Collection
    {
        $streams = match (key($queries)) {
            'all' => $this->eventStreamProvider->allWithoutInternal(),
            'categories' => $this->eventStreamProvider->filterByCategories($queries['categories']),
            default => $this->handleStreamNames($queries['names'] ?? []),
        };

        return new Collection($streams);
    }

    protected function handleStreamNames(array $streamNames): array
    {
        if (empty($streamNames)) {
            throw new InvalidArgumentException('Stream names can not be empty');
        }

        return $streamNames;
    }
}
