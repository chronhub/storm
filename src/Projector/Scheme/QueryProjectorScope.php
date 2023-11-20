<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScopeInterface;
use Closure;

final readonly class QueryProjectorScope implements QueryProjectorScopeInterface
{
    public function __construct(
        private QueryProjector $projector,
        private SystemClock $clock,
        private Closure $currentStreamName
    ) {
    }

    public function stop(): void
    {
        $this->projector->stop();
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
