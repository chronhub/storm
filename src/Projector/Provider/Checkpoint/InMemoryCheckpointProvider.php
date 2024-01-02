<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Provider\Checkpoint;

use Chronhub\Storm\Contracts\Projector\RecognitionProvider;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Illuminate\Support\Collection;

class InMemoryCheckpointProvider implements RecognitionProvider
{
    private Collection $checkpoints;

    public function __construct()
    {
        $this->checkpoints = new Collection();
    }

    public function insert(CheckpointDTO $checkpoint): void
    {
        $model = $this->newCheckpoint($checkpoint);

        $this->validateBeforeInsert($model);

        $this->checkpoints->put($model->id(), $model);
    }

    /**
     * @param array<CheckpointDTO> $checkpoints
     */
    public function insertBatch(array $checkpoints): void
    {
        // do we need to fail all when one fails?
        $models = [];

        foreach ($checkpoints as $checkpoint) {
            $model = $this->newCheckpoint($checkpoint);

            $this->validateBeforeInsert($model);

            $models[] = $model;
        }

        $this->checkpoints = $this->checkpoints->merge($models);
    }

    /**
     * Get the last checkpoint for each stream
     *
     * @return Collection<InMemoryCheckpointModel>
     */
    public function lastCheckpointByProjectionName(string $projectionName): Collection
    {
        return $this->checkpoints
            ->filter(fn (InMemoryCheckpointModel $model) => $model->projectionName === $projectionName)
            ->groupBy(fn (InMemoryCheckpointModel $model) => $model->streamName)
            ->map(fn (Collection $groupedCheckpoints) => $groupedCheckpoints
                ->sortByDesc(fn (InMemoryCheckpointModel $model) => $model->createdAt())
                ->first()
            );
    }

    /**
     * Get the last checkpoint for a projection and stream
     */
    public function lastCheckpoint(string $projectionName, string $streamName): ?InMemoryCheckpointModel
    {
        return $this->checkpoints
            ->filter($this->findByNames($projectionName, $streamName))
            ->sortByDesc(fn (InMemoryCheckpointModel $model) => $model->createdAt())
            ->first();
    }

    /**
     * Delete checkpoint by projection name
     */
    public function delete(string $projectionName): void
    {
        $this->checkpoints = $this->checkpoints->reject(
            fn (InMemoryCheckpointModel $model) => $model->projectionName === $projectionName
        );
    }

    /**
     * Delete checkpoint by projection name and stream name
     */
    public function deleteByNames(string $projectionName, string $streamName): void
    {
        $this->checkpoints = $this->checkpoints->reject(
            $this->findByNames($projectionName, $streamName)
        );
    }

    /**
     * Delete checkpoint by projection name, stream name and position
     */
    public function deleteById(string $projectionName, string $streamName, int $position): void
    {
        $this->checkpoints = $this->checkpoints->reject(
            fn (InMemoryCheckpointModel $model) => $model->projectionName === $projectionName
                && $model->streamName === $streamName
                && $model->position === $position
        );
    }

    /**
     * Delete checkpoint where created at is lower than given datetime
     */
    public function deleteByDateLowerThan(string $projectionName, string $datetime): void
    {
        $this->checkpoints = $this->checkpoints->reject(
            fn (InMemoryCheckpointModel $model) => $model->projectionName === $projectionName
                && $model->createdAt < $datetime
        );
    }

    public function deleteAll(): void
    {
        $this->checkpoints = new Collection();
    }

    public function all(): Collection
    {
        return $this->checkpoints;
    }

    private function validateBeforeInsert(InMemoryCheckpointModel $model): void
    {
        // by now we raise exceptions, but we probably fail silently and log it in repository
        if ($this->checkpoints->contains($model->id())) {
            throw new InvalidArgumentException("Checkpoint with id {$model->id()} already exists");
        }

        $lastCheckpoint = $this->lastCheckpoint($model->projectionName, $model->streamName);

        if ($lastCheckpoint === null) {
            return;
        }

        if ($lastCheckpoint->position() >= $model->position()) {
            throw new InvalidArgumentException("Checkpoint position {$model->position()} is less or equal than last checkpoint position {$lastCheckpoint->position()}");
        }

        if ($lastCheckpoint->createdAt() >= $model->createdAt()) {
            throw new InvalidArgumentException("Checkpoint created at {$model->createdAt()} is less or equal than last checkpoint created at {$lastCheckpoint->createdAt()}");
        }
    }

    private function findByNames(string $projectionName, string $streamName): callable
    {
        return fn (InMemoryCheckpointModel $model) => $model->projectionName === $projectionName
            && $model->streamName === $streamName;
    }

    private function newCheckpoint(CheckpointDTO $checkpoint): InMemoryCheckpointModel
    {
        return new InMemoryCheckpointModel(
            $checkpoint->projectionName,
            $checkpoint->streamName,
            $checkpoint->position,
            $checkpoint->createdAt,
            $checkpoint->gaps
        );
    }
}
