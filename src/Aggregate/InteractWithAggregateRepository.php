<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Throwable;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use function count;
use function reset;
use function array_map;

trait InteractWithAggregateRepository
{
    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot
    {
        if ($this->cache->has($aggregateId)) {
            return $this->cache->get($aggregateId);
        }

        $aggregateRoot = $this->reconstituteAggregateRoot($aggregateId);

        if ($aggregateRoot) {
            $this->cache->put($aggregateRoot);
        }

        return $aggregateRoot;
    }

    public function store(AggregateRoot $aggregateRoot): void
    {
        $this->aggregateType->isSupported($aggregateRoot::class);

        $events = $this->releaseDecoratedEvents($aggregateRoot);

        $firstEvent = reset($events);

        if (! $firstEvent) {
            return;
        }

        $this->storeStream($firstEvent, $aggregateRoot, $events);
    }

    /**
     * @param array{DomainEvent} $releasedEvents
     *
     * @throws Throwable
     */
    protected function storeStream(DomainEvent $firstEvent, AggregateRoot $aggregateRoot, array $releasedEvents): void
    {
        $stream = $this->producer->toStream($aggregateRoot->aggregateId(), $releasedEvents);

        try {
            $this->producer->isFirstCommit($firstEvent)
                ? $this->chronicler->firstCommit($stream)
                : $this->chronicler->amend($stream);

            $this->cache->put($aggregateRoot);
        } catch (Throwable $exception) {
            $this->cache->forget($aggregateRoot->aggregateId());

            throw $exception;
        }
    }

    /**
     * @return array{DomainEvent}
     */
    protected function releaseDecoratedEvents(AggregateRoot $aggregateRoot): array
    {
        $events = $aggregateRoot->releaseEvents();

        if (count($events) === 0) {
            return [];
        }

        $version = $aggregateRoot->version() - count($events);

        $aggregateId = $aggregateRoot->aggregateId();

        $headers = [
            EventHeader::AGGREGATE_ID => $aggregateId->toString(),
            EventHeader::AGGREGATE_ID_TYPE => $aggregateId::class,
            EventHeader::AGGREGATE_TYPE => $aggregateRoot::class,
        ];

        return array_map(function (DomainEvent $event) use ($headers, &$version) {
            return $this->messageDecorator->decorate(
                new Message($event, $headers + [EventHeader::AGGREGATE_VERSION => ++$version])
            )->event();
        }, $events);
    }
}
