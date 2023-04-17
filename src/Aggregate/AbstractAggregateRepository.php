<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateCache;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateRepository;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Contracts\Stream\StreamProducer;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\DomainEvent;
use Generator;
use Throwable;
use function array_map;
use function count;
use function reset;

abstract readonly class AbstractAggregateRepository implements AggregateRepository
{
    public function __construct(
        public Chronicler $chronicler,
        public StreamProducer $streamProducer,
        public AggregateCache $aggregateCache,
        protected AggregateType $aggregateType,
        protected MessageDecorator $messageDecorator
    ) {
    }

    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot
    {
        if ($this->aggregateCache->has($aggregateId)) {
            return $this->aggregateCache->get($aggregateId);
        }

        $aggregateRoot = $this->reconstituteAggregateRoot($aggregateId);

        if ($aggregateRoot instanceof AggregateRoot) {
            $this->aggregateCache->put($aggregateRoot);
        }

        return $aggregateRoot;
    }

    public function retrieveFiltered(AggregateIdentity $aggregateId, QueryFilter $queryFilter): ?AggregateRoot
    {
        return $this->reconstituteAggregateRoot($aggregateId, $queryFilter);
    }

    public function store(AggregateRoot $aggregateRoot): void
    {
        $this->aggregateType->assertAggregateIsSupported($aggregateRoot::class);

        $events = $this->releaseDecoratedEvents($aggregateRoot);

        $firstEvent = reset($events);

        if (! $firstEvent instanceof DomainEvent) {
            return;
        }

        $this->storeStream($firstEvent, $aggregateRoot, $events);
    }

    protected function reconstituteAggregateRoot(
        AggregateIdentity $aggregateId,
        ?QueryFilter $queryFilter = null
    ): ?AggregateRoot {
        try {
            $history = $this->fromHistory($aggregateId, $queryFilter);

            if (! $history->valid()) {
                return null;
            }

            /** @var AggregateRoot $aggregateRoot */
            $aggregateRoot = $this->aggregateType->from($history->current());

            return $aggregateRoot::reconstitute($aggregateId, $history);
        } catch (StreamNotFound) {
            return null;
        }
    }

    /**
     * @return Generator{DomainEvent}
     *
     * @throws StreamNotFound
     */
    protected function fromHistory(
        AggregateIdentity $aggregateId,
        ?QueryFilter $queryFilter
    ): Generator {
        $streamName = $this->streamProducer->toStreamName($aggregateId);

        if ($queryFilter instanceof QueryFilter) {
            return $this->chronicler->retrieveFiltered($streamName, $queryFilter);
        }

        return $this->chronicler->retrieveAll($streamName, $aggregateId);
    }

    /**
     * @param array{DomainEvent} $releasedEvents
     *
     * @throws Throwable
     */
    protected function storeStream(
        DomainEvent $firstEvent,
        AggregateRoot $aggregateRoot,
        array $releasedEvents
    ): void {
        $stream = $this->streamProducer->toStream($aggregateRoot->aggregateId(), $releasedEvents);

        try {
            $this->streamProducer->isFirstCommit($firstEvent)
                ? $this->chronicler->firstCommit($stream)
                : $this->chronicler->amend($stream);

            $this->aggregateCache->put($aggregateRoot);
        } catch (Throwable $exception) {
            $this->aggregateCache->forget($aggregateRoot->aggregateId());

            throw $exception;
        }
    }

    /**
     * @return array{DomainEvent}|array
     */
    protected function releaseDecoratedEvents(AggregateRoot $aggregateRoot): array
    {
        $events = $aggregateRoot->releaseEvents();

        if (! reset($events)) {
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
