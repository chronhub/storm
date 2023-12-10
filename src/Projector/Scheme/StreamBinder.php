<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\StreamManager;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Reporter\DomainEvent;
use Illuminate\Support\Collection;

final class StreamBinder implements StreamManager
{
    /**
     * @var Collection<string,int>
     */
    private Collection $streamPosition;

    public function __construct(private readonly EventStreamLoader $eventStreamLoader)
    {
        $this->streamPosition = new Collection();
    }

    public function discover(array $queries): void
    {
        $container = $this->eventStreamLoader
            ->loadFrom($queries)
            ->mapWithKeys(fn (string $streamName): array => [$streamName => 0]);

        $this->streamPosition = $container->merge($this->streamPosition);
    }

    public function merge(array $streamsPositions): void
    {
        $this->streamPosition = $this->streamPosition->merge($streamsPositions);
    }

    public function bind(string $streamName, int $expectedPosition, DomainEvent $event): bool
    {
        if (! $this->hasStream($streamName)) {
            throw new RuntimeException("Stream $streamName is not watched");
        }

        $this->streamPosition[$streamName] = $expectedPosition;

        return true;
    }

    public function isAvailable(string $streamName, int $expectedPosition): bool
    {
        if (! $this->hasStream($streamName)) {
            throw new RuntimeException("Stream $streamName is not watched");
        }

        return $expectedPosition === $this->streamPosition[$streamName] + 1;
    }

    public function resets(): void
    {
        $this->streamPosition = new Collection();
    }

    public function hasStream(string $streamName): bool
    {
        return $this->streamPosition->has($streamName);
    }

    public function all(): array
    {
        return $this->streamPosition->toArray();
    }

    public function jsonSerialize(): array
    {
        return $this->all();
    }
}
