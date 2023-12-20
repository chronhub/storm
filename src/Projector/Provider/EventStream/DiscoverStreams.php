<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Provider\EventStream;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

use function array_unique;
use function count;

final readonly class DiscoverStreams
{
    public function __construct(private array $streams)
    {
        $this->validateStreams();
    }

    public function __invoke(EventStreamProvider $provider): array
    {
        return $provider->filterByAscendantStreams($this->streams);
    }

    private function validateStreams(): void
    {
        if ($this->streams === []) {
            throw new InvalidArgumentException('Streams cannot be empty');
        }

        if (count($this->streams) !== count(array_unique($this->streams))) {
            throw new InvalidArgumentException('Streams cannot contain duplicate');
        }
    }
}
