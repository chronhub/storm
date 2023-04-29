<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Chronhub\Storm\Contracts\Aggregate\AggregateCache;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateRepository;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Stream\StreamProducer;

final readonly class GenericAggregateRepository implements AggregateRepository
{
    use InteractWithAggregateRepository;

    public function __construct(
        protected Chronicler $chronicler,
        protected StreamProducer $streamProducer,
        protected AggregateCache $aggregateCache,
        protected AggregateType $aggregateType,
        protected AggregateReleaser $aggregateReleaser,
    ) {
    }

    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot
    {
        if ($this->aggregateCache->has($aggregateId)) {
            return $this->aggregateCache->get($aggregateId);
        }

        $aggregate = $this->reconstituteAggregate($aggregateId);

        if ($aggregate instanceof AggregateRoot) {
            $this->aggregateCache->put($aggregate);
        }

        return $aggregate;
    }
}
