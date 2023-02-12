<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use function in_array;
use function array_fill;

class StreamCache
{
    /**
     * @var array<int, string|null>
     */
    protected array $container;

    protected int $position = -1;

    public function __construct(private readonly int $cacheSize)
    {
        if ($cacheSize <= 0) {
            throw new InvalidArgumentException('Stream cache size must be greater than 0');
        }

        $this->container = array_fill(0, $cacheSize, null);
    }

    public function push(string $streamName): void
    {
        $this->position = ++$this->position % $this->cacheSize;

        $this->container[$this->position] = $streamName;
    }

    public function has(string $streamName): bool
    {
        return in_array($streamName, $this->container, true);
    }

    public function all(): array
    {
        return $this->container;
    }
}
