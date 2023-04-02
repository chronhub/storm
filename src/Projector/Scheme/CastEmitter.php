<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\EmitterProjector;
use Chronhub\Storm\Contracts\Projector\EmitterCasterInterface;

final class CastEmitter implements EmitterCasterInterface
{
    private ?string $currentStreamName = null;

    public function __construct(
        private readonly EmitterProjector $projector,
        private readonly SystemClock $clock,
        ?string &$currentStreamName
    ) {
        $this->currentStreamName = &$currentStreamName;
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $this->projector->linkTo($streamName, $event);
    }

    public function emit(DomainEvent $event): void
    {
        $this->projector->emit($event);
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
