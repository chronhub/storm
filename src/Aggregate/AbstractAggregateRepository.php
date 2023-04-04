<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Chronhub\Storm\Contracts\Aggregate\AggregateCache;
use Chronhub\Storm\Contracts\Aggregate\AggregateRepository;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Contracts\Stream\StreamProducer;

abstract readonly class AbstractAggregateRepository implements AggregateRepository
{
    use ReconstituteAggregate;
    use InteractWithAggregateRepository;

    public function __construct(
        public Chronicler $chronicler,
        public StreamProducer $streamProducer,
        public AggregateCache $aggregateCache,
        protected AggregateType $aggregateType,
        protected MessageDecorator $messageDecorator
    ) {
    }
}
