<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Provider\Checkpoint;

use Chronhub\Storm\Contracts\Projector\RecognitionProvider;
use Chronhub\Storm\Contracts\Projector\SnapshotRepository;
use Chronhub\Storm\Projector\Checkpoint\Checkpoint;
use Illuminate\Support\Collection;

use function json_encode;

final readonly class SnapshotCheckpointRepository implements SnapshotRepository
{
    public function __construct(private RecognitionProvider $recognitionProvider)
    {
    }

    public function snapshot(string $projectionName, Checkpoint $checkpoint): void
    {
        $dto = new CheckpointDTO(
            $projectionName,
            $checkpoint->streamName,
            $checkpoint->position,
            $checkpoint->eventTime,
            $checkpoint->createdAt,
            json_encode($checkpoint->gaps) //fixme
        );

        $this->recognitionProvider->insert($dto);
    }

    public function deleteByProjectionName(string $projectionName): void
    {
        $this->recognitionProvider->delete($projectionName);
    }

    public function shouldSnapshot(string $projectionName, Checkpoint $checkpoint): bool
    {
        $checkpointId = new CheckpointId($projectionName, $checkpoint->streamName, $checkpoint->position);

        return $this->recognitionProvider->exists($checkpointId);
    }

    public function all(): Collection
    {
        return $this->recognitionProvider->all();
    }
}
