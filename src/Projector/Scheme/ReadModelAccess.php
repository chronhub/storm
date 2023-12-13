<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ReadModelManagement;
use Chronhub\Storm\Contracts\Projector\ReadModelScope;
use Chronhub\Storm\Contracts\Projector\StackedReadModel;

final readonly class ReadModelAccess implements ReadModelScope
{
    public function __construct(private ReadModelManagement $management)
    {
    }

    public function stop(): void
    {
        $this->management->close();
    }

    public function readModel(): StackedReadModel
    {
        return $this->management->getReadModel();
    }

    public function stack(string $operation, ...$arguments): void
    {
        $this->management->getReadModel()->stack($operation, ...$arguments);
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
