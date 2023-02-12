<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorCaster;

final class ReadModelCaster implements ReadModelProjectorCaster
{
    private ?string $currentStreamName = null;

    public function __construct(private readonly ReadModelProjector $projector,
                                ?string &$currentStreamName)
    {
        $this->currentStreamName = &$currentStreamName;
    }

    public function stop(): void
    {
        $this->projector->stop();
    }

    public function readModel(): ReadModel
    {
        return $this->projector->readModel();
    }

    public function streamName(): ?string
    {
        return $this->currentStreamName;
    }
}
