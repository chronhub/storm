<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs;

use Chronhub\Storm\Aggregate\InteractWithAggregateRepository;
use Chronhub\Storm\Contracts\Aggregate\AggregateCache;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Contracts\Stream\StreamProducer;

final class InteractWithAggregateRepositoryStub
{
    use InteractWithAggregateRepository;

    private ?AggregateRootStub $someAggregateRoot = null;

    public function __construct(public readonly Chronicler $chronicler,
                                public readonly StreamProducer $streamProducer,
                                public readonly AggregateCache $aggregateCache,
                                protected readonly AggregateType $aggregateType,
                                protected readonly MessageDecorator $messageDecorator)
    {
    }

    public function withReconstituteAggregateRoot(?AggregateRootStub $someAggregateRoot): void
    {
        $this->someAggregateRoot = $someAggregateRoot;
    }

    protected function reconstituteAggregateRoot(AggregateIdentity $aggregateId): ?AggregateRootStub
    {
        return $this->someAggregateRoot;
    }
}
