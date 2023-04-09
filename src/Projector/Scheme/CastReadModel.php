<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelCasterInterface;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;

final class CastReadModel implements ReadModelCasterInterface
{
    private ?string $currentStreamName = null;

    public function __construct(
        private readonly ReadModelProjector $projector,
        private readonly SystemClock $clock,
        ?string &$currentStreamName
    ) {
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

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
