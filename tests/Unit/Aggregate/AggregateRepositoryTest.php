<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Message\NoOpMessageDecorator;
use Chronhub\Storm\Aggregate\AggregateRepository;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Stream\StreamProducer;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Aggregate\AggregateCache;
use Chronhub\Storm\Aggregate\AbstractAggregateRepository;

#[CoversClass(AggregateRepository::class)]
#[CoversClass(AbstractAggregateRepository::class)]
final class AggregateRepositoryTest extends UnitTestCase
{
    private Chronicler|MockObject $chronicler;

    private StreamProducer|MockObject $streamProducer;

    private AggregateType|MockObject $aggregateType;

    private AggregateCache|MockObject $aggregateCache;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->chronicler = $this->createMock(Chronicler::class);
        $this->streamProducer = $this->createMock(StreamProducer::class);
        $this->aggregateType = $this->createMock(AggregateType::class);
        $this->aggregateCache = $this->createMock(AggregateCache::class);
    }

    #[Test]
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
