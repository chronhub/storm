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

        $this->chronicler = $this->prophesize(Chronicler::class);
        $this->streamProducer = $this->prophesize(StreamProducer::class);
        $this->aggregateType = $this->prophesize(AggregateType::class);
        $this->aggregateCache = $this->prophesize(AggregateCache::class);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $repository = new AggregateRepository(
            $this->chronicler->reveal(),
            $this->streamProducer->reveal(),
            $this->aggregateCache->reveal(),
            $this->aggregateType->reveal(),
            new NoOpMessageDecorator()
        );

        $this->assertSame($this->chronicler->reveal(), $repository->chronicler);
        $this->assertSame($this->streamProducer->reveal(), $repository->streamProducer);
        $this->assertSame($this->aggregateCache->reveal(), $repository->aggregateCache);
    }
}
