<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Provider\Checkpoint;

use Chronhub\Storm\Contracts\Projector\RecognitionProvider;
use Chronhub\Storm\Contracts\Projector\RecognitionRepository;
use Chronhub\Storm\Projector\Stream\Checkpoint;

use function json_encode;

final readonly class InMemoryCheckpointRepository implements RecognitionRepository
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
            $checkpoint->createdAt,
            json_encode($checkpoint->gaps) //fixme
        );

        $this->recognitionProvider->insert($dto);
    }
}
