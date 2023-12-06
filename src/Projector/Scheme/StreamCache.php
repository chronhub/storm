<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\StreamCacheInterface;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

use function array_fill;
use function in_array;

final class StreamCache implements StreamCacheInterface
{
    private array $buffer = [];

    private int $position = -1;

    public function __construct(public readonly int $cacheSize)
    {
        if ($cacheSize <= 0) {
            throw new InvalidArgumentException('Stream cache size must be greater than 0');
        }

        $this->buffer = array_fill(0, $cacheSize, null);
    }

    public function push(string $streamName): void
    {
        if ($this->has($streamName)) {
            throw new InvalidArgumentException("Stream $streamName is already in the cache");
        }

        $this->position = ++$this->position % $this->cacheSize;

        $this->buffer[$this->position] = $streamName;
    }

    public function has(string $streamName): bool
    {
        return in_array($streamName, $this->buffer, true);
    }

    /**
     * @return array<int<0,max>,string|null>
     */
    public function jsonSerialize(): array
    {
        return $this->buffer;
    }
}
