<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use ArrayAccess;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelScope;
use Chronhub\Storm\Contracts\Projector\StackedReadModel;
use Chronhub\Storm\Projector\Subscription\Notification;
use Chronhub\Storm\Projector\Subscription\Notification\GetStreamName;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionClosed;

final class ReadModelAccess implements ArrayAccess, ReadModelScope
{
    use ScopeBehaviour;

    public function __construct(
        private readonly Notification $notification,
        private readonly ReadModel $readModel,
        private readonly SystemClock $clock
    ) {
    }

    public function stop(): void
    {
        $this->notification->trigger(new ProjectionClosed());
    }

    // todo remove
    public function readModel(): StackedReadModel
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
        return $this->notification->listen(GetStreamName::class);
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
