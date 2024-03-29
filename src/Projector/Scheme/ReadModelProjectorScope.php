<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorScopeInterface;
use Closure;

final readonly class ReadModelProjectorScope implements ReadModelProjectorScopeInterface
{
    public function __construct(
        private ReadModelProjector $projector,
        private SystemClock $clock,
        private Closure $currentStreamName
    ) {
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
        return ($this->currentStreamName)();
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
