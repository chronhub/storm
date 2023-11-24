<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Illuminate\Support\Collection;

use function array_is_list;

class GapCollection
{
    /**
     * @var Collection<array<positive-int,bool>>
     */
    protected Collection $gaps;

    public function __construct()
    {
        $this->gaps = new Collection();
    }

    /**
     * Adds a new event position and its confirmation status
     *
     * @param positive-int $eventPosition The position of the event.
     * @param bool         $confirmed     The confirmation status of gap.
     */
    public function put(int $eventPosition, bool $confirmed): void
    {
        if ($eventPosition < 1) {
            throw new InvalidArgumentException('Event position must be greater than 0');
        }

        $this->gaps->put($eventPosition, $confirmed);
    }

    /**
     * Removes the specified stream gap if exists.
     *
     * @param positive-int $eventPosition
     */
    public function remove(int $eventPosition): void
    {
        $gap = $this->gaps->get($eventPosition);

        if ($gap === null) {
            return;
        }

        $this->gaps->forget($eventPosition);
    }

    /**
     * Merge remote with local stream gaps
     *
     * @param array<positive-int> $streamGaps
     */
    public function merge(array $streamGaps): void
    {
        if ($streamGaps === []) {
            return;
        }

        if (! array_is_list($streamGaps)) {
            throw new InvalidArgumentException('Stream gaps must be not be an associative array');
        }

        foreach ($streamGaps as $streamGap) {
            $this->put($streamGap, true);
        }
    }

    /**
     * Filter confirmed gaps and remove unconfirmed local gaps.
     *
     * @return array<positive-int>
     */
    public function filterConfirmedGaps(): array
    {
        $this->gaps = $this->gaps->reject(fn (bool $confirmed): bool => $confirmed === false);

        return $this->gaps->keys()->values()->toArray();
    }

    public function all(): Collection
    {
        return clone $this->gaps;
    }
}
