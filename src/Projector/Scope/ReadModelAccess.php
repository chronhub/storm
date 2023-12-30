<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use ArrayAccess;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelScope;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionClosed;
use Chronhub\Storm\Projector\Subscription\Notification\CurrentProcessedStream;

final class ReadModelAccess implements ArrayAccess, ReadModelScope
{
    use ScopeBehaviour;

    public function __construct(
        private readonly NotificationHub $hook,
        private readonly ReadModel $readModel,
        private readonly SystemClock $clock
    ) {
    }

    public function stop(): void
    {
        $this->hook->trigger(new ProjectionClosed());
    }

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    public function stack(string $operation, ...$arguments): self
    {
        $this->readModel()->stack($operation, ...$arguments);

        return $this;
    }

    public function streamName(): string
    {
        return $this->hook->expect(CurrentProcessedStream::class);
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
