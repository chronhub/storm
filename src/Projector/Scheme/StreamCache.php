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
    private array $buffer = [];

    private int $position = -1;

    public function __construct(private readonly int $cacheSize)
    {
        if ($cacheSize <= 0) {
            throw new InvalidArgumentException('Stream cache size must be greater than 0');
        }

        $this->buffer = array_fill(0, $cacheSize, null);
    }

    /**
     * Add or replace stream name at the current position in the circular buffer
     *
     * @param non-empty-string $streamName
     *
     * @throws InvalidArgumentException When stream name is already in the cache
     */
    public function push(string $streamName): void
    {
        if ($this->has($streamName)) {
            throw new InvalidArgumentException("Stream $streamName is already in the cache");
        }

        $this->position = ++$this->position % $this->cacheSize;

        $this->buffer[$this->position] = $streamName;
    }

    /**
     * Check if stream name is in cache
     *
     * @param non-empty-string $streamName
     */
    public function has(string $streamName): bool
    {
        return in_array($streamName, $this->buffer, true);
    }

    public function jsonSerialize(): array
    {
        return $this->buffer;
    }
}
