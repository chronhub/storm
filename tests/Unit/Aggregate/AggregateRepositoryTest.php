<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Message\NoOpMessageDecorator;
use Chronhub\Storm\Aggregate\AggregateRepository;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Stream\StreamProducer;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Aggregate\AggregateCache;

final class AggregateRepositoryTest extends ProphecyTestCase
{
    private Chronicler|ObjectProphecy $chronicler;

    private StreamProducer|ObjectProphecy $streamProducer;

    private AggregateType|ObjectProphecy $aggregateType;

    private AggregateCache|ObjectProphecy $aggregateCache;

    public function setUp(): void
    {
        parent::setUp();

        $this->chronicler = $this->createStub(Chronicler::class);
        $this->streamProducer = $this->createStub(StreamProducer::class);
        $this->aggregateType = $this->createStub(AggregateType::class);
        $this->aggregateCache = $this->createStub(AggregateCache::class);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $repository = new AggregateRepository(
            $this->chronicler,
            $this->streamProducer,
            $this->aggregateCache,
            $this->aggregateType,
            new NoOpMessageDecorator()
        );

        $this->assertSame($this->chronicler, $repository->chronicler);
        $this->assertSame($this->streamProducer, $repository->streamProducer);
        $this->assertSame($this->aggregateCache, $repository->aggregateCache);
    }
}
