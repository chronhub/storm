<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scope;

use ArrayAccess;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\EmitterManagement;
use Chronhub\Storm\Contracts\Projector\EmitterScope;
use Chronhub\Storm\Reporter\DomainEvent;

final class EmitterAccess implements ArrayAccess, EmitterScope
{
    use ScopeBehaviour;

    public function __construct(private readonly EmitterManagement $management)
    {
    }

    public function emit(DomainEvent $event): void
    {
        $this->management->emit($event);
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $this->management->linkTo($streamName, $event);
    }

    public function stop(): void
    {
        $this->management->close();
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
