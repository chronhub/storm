<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use JsonSerializable;

use function array_fill;
use function in_array;

final class StreamCache implements JsonSerializable
{
    /**
     * @var array<int<0,max>,string|null>
     */
    private array $container = [];

    private int $position = -1;

    public function __construct(private readonly int $cacheSize)
    {
        if ($cacheSize <= 0) {
            throw new InvalidArgumentException('Stream cache size must be greater than 0');
        }

        $this->container = array_fill(0, $cacheSize, null);
    }

    public function push(string $streamName): void
    {
        if ($this->has($streamName)) {
            throw new InvalidArgumentException("Stream $streamName is already in the cache");
        }

        $this->position = ++$this->position % $this->cacheSize;

        $this->container[$this->position] = $streamName;
    }

    public function has(string $streamName): bool
    {
        return in_array($streamName, $this->container, true);
    }

    public function jsonSerialize(): array
    {
        return $this->container;
    }
}
