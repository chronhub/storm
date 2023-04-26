<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Chronhub\Storm\Contracts\Aggregate\AggregateCache;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateQueryRepository;
use Chronhub\Storm\Contracts\Aggregate\AggregateRepository;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Stream\StreamProducer;
use Generator;
use Throwable;

final readonly class GenericAggregateRepository implements AggregateRepository
{
    public function __construct(
        private Chronicler $chronicler,
        private StreamProducer $streamProducer,
        private AggregateCache $aggregateCache,
        private AggregateType $aggregateType,
        private AggregateReleaser $aggregateReleaser,
        private AggregateQueryRepository $aggregateQuery,
    ) {
    }

    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot
    {
        if ($this->aggregateCache->has($aggregateId)) {
            return $this->aggregateCache->get($aggregateId);
        }

        $aggregate = $this->aggregateQuery->retrieve($aggregateId);

        if ($aggregate instanceof AggregateRoot) {
            $this->aggregateCache->put($aggregate);
        }

        return $aggregate;
    }

    public function retrieveFiltered(AggregateIdentity $aggregateId, QueryFilter $queryFilter): ?AggregateRoot
    {
        return $this->aggregateQuery->retrieveFiltered($aggregateId, $queryFilter);
    }

    public function retrieveHistory(AggregateIdentity $aggregateId, ?QueryFilter $queryFilter): Generator
    {
        return $this->aggregateQuery->retrieveHistory($aggregateId, $queryFilter);
    }

    public function store(AggregateRoot $aggregateRoot): void
    {
        $this->aggregateType->assertAggregateIsSupported($aggregateRoot::class);

        $events = $this->aggregateReleaser->releaseEvents($aggregateRoot);

        if ($events === null || $events === []) {
            return;
        }

        $stream = $this->streamProducer->toStream($aggregateRoot->aggregateId(), $events);

        try {
            $this->streamProducer->isFirstCommit($events[0])
                ? $this->chronicler->firstCommit($stream)
                : $this->chronicler->amend($stream);

            $this->aggregateCache->put($aggregateRoot);
        } catch (Throwable $exception) {
            $this->aggregateCache->forget($aggregateRoot->aggregateId());

            throw $exception;
        }
    }
}
