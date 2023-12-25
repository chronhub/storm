<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Stream;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Illuminate\Support\Collection;

use function array_merge;
use function in_array;
use function max;
use function min;
use function range;

class CheckpointCollection
{
    /**
     * @var Collection<string,Checkpoint>
     */
    private Collection $checkpoints;

    public function __construct(protected readonly SystemClock $clock)
    {
        $this->checkpoints = new Collection();
    }

    public function onDiscover(string ...$streamNames): void
    {
        foreach ($streamNames as $streamName) {
            if (! $this->has($streamName)) {
                $this->checkpoints->put($streamName, $this->newCheckpoint($streamName, 0, []));
            }
        }
    }

    public function last(string $streamName): Checkpoint
    {
        return $this->checkpoints->get($streamName);
    }

    public function next(string $streamName, int $position, array $gaps): void
    {
        $this->checkpoints->put($streamName, $this->newCheckpoint($streamName, $position, $gaps));
    }

    public function nextWithGap(Checkpoint $checkpoint, int $position): void
    {
        if ($position - $checkpoint->position < 0) {
            throw new InvalidArgumentException('Invalid position: no gap or checkpoints are outdated');
        }

        $lastCheckpointPosition = $checkpoint->position;

        // checkMe: probably tolerate one gap but no more?
        $gapsToAdd = range($lastCheckpointPosition + 1, $position - 1);

        // todo: test in integration
        // use cases when projection move to one recovered checkpoint to another
        // meant the projection state is probably invalid
        foreach ($gapsToAdd as $gap) {
            if (in_array($gap, $checkpoint->gaps, true)) {
                throw new InvalidArgumentException('Gap '.$gap.' already registered');
            }
        }

        if ($checkpoint->gaps !== [] && (max($checkpoint->gaps) > min($gapsToAdd))) {
            throw new InvalidArgumentException('Cannot register gaps which are lower than previous registered gaps');
        }

        // another scenario is the event position is no longer part of the gaps,
        // meaning the gap was resolved

        $newCheckpoint = $this->newCheckpoint(
            $checkpoint->streamName,
            $position,
            array_merge($checkpoint->gaps, $gapsToAdd)
        );

        $this->checkpoints->put($checkpoint->streamName, $newCheckpoint);
    }

    public function update(string $streamName, Checkpoint $checkpoint): void
    {
        $this->checkpoints->put($streamName, $checkpoint);
    }

    public function has(string $streamName): bool
    {
        return $this->checkpoints->has($streamName);
    }

    public function flush(): void
    {
        $this->checkpoints = new Collection();
    }

    public function all(): Collection
    {
        return $this->checkpoints;
    }

    private function newCheckpoint(string $streamName, int $position, array $gaps): Checkpoint
    {
        return CheckpointFactory::from($streamName, $position, $this->clock->toString(), $gaps);
    }
}