<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelManagement;
use Chronhub\Storm\Contracts\Projector\ReadModelScope;

final readonly class ReadModelAccess implements ReadModelScope
{
    public function __construct(private ReadModelManagement $management)
    {
    }

    public function stop(): void
    {
        $this->management->close();
    }

    public function readModel(): ReadModel
    {
        return $this->management->getReadModel();
    }

    public function streamName(): string
    {
        return $this->management->getCurrentStreamName();
    }

    public function clock(): SystemClock
    {
        return $this->management->getClock();
    }
}
