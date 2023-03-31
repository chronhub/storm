<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\QueryProjectorCaster;

final class QueryCaster implements QueryProjectorCaster
{
    private ?string $currentStreamName = null;

    public function __construct(
        private readonly QueryProjector $projector,
        private readonly SystemClock $clock,
        ?string &$currentStreamName
    ) {
        $this->currentStreamName = &$currentStreamName;
    }

    public function stop(): void
    {
        $this->projector->stop();
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
