<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Stream;

use Chronhub\Storm\Contracts\Projector\CheckpointRecognition;
use Chronhub\Storm\Contracts\Projector\GapRecognition;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

use function array_map;
use function array_merge;
use function in_array;

final class CheckpointManager implements CheckpointRecognition
{
    private array $eventStreams = [];

    public function __construct(
        private readonly CheckpointCollection $checkpoints,
        private readonly GapRecognition $gapDetector,
    ) {
    }

    public function refreshStreams(array $eventStreams): void
    {
        $this->eventStreams = array_merge($this->eventStreams, $eventStreams);

        $this->checkpoints->onDiscover(...$eventStreams);
    }

    public function insert(string $streamName, int $position): Checkpoint
    {
        $this->validate($streamName, $position);

        $checkpoint = $this->checkpoints->last($streamName);

        if ($position < $checkpoint->position) {
            throw new InvalidArgumentException("Position given for stream $streamName is outdated");
        }

        if ($this->hasNextPosition($checkpoint, $position)) {
            $this->checkpoints->next($streamName, $position, $checkpoint->gaps, null);

            return $this->checkpoints->last($streamName);
        }

        return $this->handleGap($streamName, $position, $checkpoint);
    }

    public function update(array $checkpoints): void
    {
        foreach ($checkpoints as $checkpoint) {
            $streamName = $checkpoint['stream_name'];

            if (in_array($streamName, $this->eventStreams, true)) {
                $this->checkpoints->update($streamName, CheckpointFactory::fromArray($checkpoint));
            }
        }
    }

    public function hasGap(): bool
    {
        return $this->gapDetector->hasGap();
    }

    public function sleepWhenGap(): void
    {
        $this->gapDetector->sleep();
    }

    public function checkpoints(): array
    {
        return $this->checkpoints->all()->toArray();
    }

    /**
     * @return array{stream_name: string, position: int<0,max>, created_at: string, gaps: array<positive-int>}
     */
    public function jsonSerialize(): array
    {
        /** @phpstan-ignore-next-line */
        return array_map(fn (Checkpoint $checkpoint): array => $checkpoint->jsonSerialize(), $this->checkpoints());
    }

    public function resets(): void
    {
        $this->checkpoints->flush();

        $this->gapDetector->reset();
    }

    private function hasNextPosition(Checkpoint $checkpoint, int $expectedPosition): bool
    {
        return $expectedPosition === $checkpoint->position + 1;
    }

    /**
     * FixMe : this is a temporary solution
     * By now the only way to make it work is to have at least two retries in gap detection
     * and assume that the last retry would fail
     */
    private function handleGap(string $streamName, int $position, Checkpoint $checkpoint): Checkpoint
    {
        if (! $this->gapDetector->isRecoverable()) {
            $this->checkpoints->nextWithGap($checkpoint, $position, GapType::IN_GAP);

            return $this->checkpoints->last($streamName);
        } elseif ($this->gapDetector->retryLeft() === 1) {
            return $this->checkpoints->newCheckpoint($streamName, $position, $checkpoint->gaps, GapType::UNRECOVERABLE_GAP);
        } else {
            return $this->checkpoints->newCheckpoint($streamName, $position, $checkpoint->gaps, GapType::RECOVERABLE_GAP);
        }
    }

    private function validate(string $streamName, int $eventPosition): void
    {
        if (! in_array($streamName, $this->eventStreams, true)) {
            throw new InvalidArgumentException("Event stream $streamName is not watched");
        }

        if ($eventPosition < 1) {
            throw new InvalidArgumentException('Event position must be greater than 0');
        }
    }
}
