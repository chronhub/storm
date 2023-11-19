<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Illuminate\Support\Collection;

class GapsCollection
{
    /**
     * @var Collection<array<int,bool>>
     */
    protected Collection $gaps;

    public function __construct()
    {
        $this->gaps = new Collection();
    }

    /**
     * Adds a new event position and its confirmation status
     *
     * @param int<1,max> $eventPosition The position of the event.
     * @param bool       $confirmed     The confirmation status of gap.
     */
    public function put(int $eventPosition, bool $confirmed): void
    {
        $this->gaps->put($eventPosition, $confirmed);
    }

    /**
     * Removes the specified stream gap if exists.
     *
     * @param int<1,max> $eventPosition
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
     * @param array<int<1,max>> $streamGaps
     */
    public function merge(array $streamGaps): void
    {
        if ($streamGaps === []) {
            return;
        }

        // checkMe what happened if we already have a gap with unconfirmed status
        foreach ($streamGaps as $streamGap) {
            $this->gaps->put($streamGap, true);
        }
    }

    /**
     * Filter confirmed gaps and remove unconfirmed local gaps when projection is persisted
     *
     * @return array<int<1,max>>
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
