<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Illuminate\Support\Collection;

use function array_fill_keys;
use function array_values;

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
        $isGapConfirmed = $this->gaps->get($eventPosition);

        if ($isGapConfirmed === null) {
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

        $confirmedGaps = array_fill_keys(array_values($streamGaps), true);

        $this->gaps->merge($confirmedGaps);
    }

    /**
     * Filter confirmed gaps and remove unconfirmed local gaps when projection is persisted
     *
     * @return array<int<1,max>>
     */
    public function filterConfirmedGaps(): array
    {
        $confirmedGaps = $this->gaps->filter(fn (bool $confirmed): bool => $confirmed);

        $this->gaps = $confirmedGaps;

        return $confirmedGaps->keys()->all();
    }

    /**
     * Return a clone of gaps collection
     */
    public function all(): Collection
    {
        return clone $this->gaps;
    }
}
