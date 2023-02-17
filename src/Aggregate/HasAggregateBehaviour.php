<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Generator;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use function end;
use function explode;

trait HasAggregateBehaviour
{
    private int $version = 0;

    /**
     * @var array<DomainEvent>
     */
    private array $recordedEvents = [];

    protected function __construct(private readonly AggregateIdentity $aggregateId)
    {
    }

    public function aggregateId(): AggregateIdentity
    {
        return $this->aggregateId;
    }

    public function version(): int
    {
        return $this->version;
    }

    protected function recordThat(DomainEvent $event): void
    {
        $this->apply($event);

        $this->recordedEvents[] = $event;
    }

    protected function apply(DomainEvent $event): void
    {
        $parts = explode('\\', $event::class);

        $this->{'apply'.end($parts)}($event);

        $this->version++;
    }

    public function releaseEvents(): array
    {
        $releasedEvents = $this->recordedEvents;

        $this->recordedEvents = [];

        return $releasedEvents;
    }

    public static function reconstitute(AggregateIdentity $aggregateId, Generator $events): ?static
    {
        $aggregateRoot = new static($aggregateId);

        foreach ($events as $event) {
            $aggregateRoot->apply($event);
        }

        $aggregateRoot->version = (int) $events->getReturn();

        return $aggregateRoot->version() > 0 ? $aggregateRoot : null;
    }
}
