<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Generator;
use Throwable;

trait InteractWithAggregateRepository
{
    public function retrieveHistory(AggregateIdentity $aggregateId, ?QueryFilter $queryFilter): Generator
    {
        $streamName = $this->streamProducer->toStreamName($aggregateId);

        if ($queryFilter instanceof QueryFilter) {
            return $this->chronicler->retrieveFiltered($streamName, $queryFilter);
        }

        return $this->chronicler->retrieveAll($streamName, $aggregateId);
    }

    public function retrieveFiltered(AggregateIdentity $aggregateId, QueryFilter $queryFilter): ?AggregateRoot
    {
        return $this->reconstituteAggregate($aggregateId, $queryFilter);
    }

    public function store(AggregateRoot $aggregateRoot): void
    {
        $this->aggregateType->assertAggregateIsSupported($aggregateRoot::class);

        $events = $this->aggregateReleaser->releaseEvents($aggregateRoot);

        if ($events === []) {
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

    protected function reconstituteAggregate(AggregateIdentity $aggregateId, ?QueryFilter $queryFilter = null): ?AggregateRoot
    {
        try {
            $history = $this->retrieveHistory($aggregateId, $queryFilter);

            if (! $history->valid()) {
                return null;
            }

            /** @var AggregateRoot $aggregate */
            $aggregate = $this->aggregateType->from($history->current());

            return $aggregate::reconstitute($aggregateId, $history);
        } catch (StreamNotFound) {
            return null;
        }
    }
}
