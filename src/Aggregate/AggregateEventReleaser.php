<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\DomainEvent;

use function array_map;
use function count;
use function reset;

class AggregateEventReleaser
{
    public function __construct(protected readonly MessageDecorator $messageDecorator)
    {
    }

    /**
     * @return array<DomainEvent>|array
     */
    public function releaseEvents(AggregateRoot $aggregate): array
    {
        $events = $aggregate->releaseEvents();

        if (! reset($events)) {
            return [];
        }

        $version = $aggregate->version() - count($events);

        return $this->decorateReleasedEvents($aggregate, $version, $events);
    }

    /**
     * @param  array<DomainEvent> $events
     * @param  positive-int       $version
     * @return array<DomainEvent>
     */
    protected function decorateReleasedEvents(AggregateRoot $aggregate, int $version, array $events): array
    {
        $headers = [
            EventHeader::AGGREGATE_ID => $aggregate->aggregateId()->toString(),
            EventHeader::AGGREGATE_ID_TYPE => $aggregate->aggregateId()::class,
            EventHeader::AGGREGATE_TYPE => $aggregate::class,
        ];

        return array_map(function (DomainEvent $event) use ($headers, &$version) {
            return $this->messageDecorator->decorate(
                new Message($event, $headers + [EventHeader::AGGREGATE_VERSION => ++$version])
            )->event();
        }, $events);
    }
}
