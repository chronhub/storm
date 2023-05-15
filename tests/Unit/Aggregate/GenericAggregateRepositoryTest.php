<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Chronhub\Storm\Aggregate\AggregateEventReleaser;
use Chronhub\Storm\Aggregate\GenericAggregateRepository;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Aggregate\AggregateCache;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateQueryRepository;
use Chronhub\Storm\Contracts\Aggregate\AggregateRepository;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Stream\StreamProducer;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use function iterator_to_array;

#[CoversClass(GenericAggregateRepository::class)]
final class GenericAggregateRepositoryTest extends UnitTestCase
{
    private Chronicler|MockObject $chronicler;

    private StreamProducer|MockObject $streamProducer;

    private AggregateType|MockObject $aggregateType;

    private AggregateCache|MockObject $aggregateCache;

    private AggregateEventReleaser|MockObject $aggregateReleaser;

    private AggregateQueryRepository|MockObject $queryRepository;

    private AggregateIdentity|MockObject $someIdentity;

    private StreamName $streamName;

    private string $identityString = '9ef864f7-43e2-48c8-9944-639a2d927a06';

    protected function setUp(): void
    {
        $this->chronicler = $this->createMock(Chronicler::class);
        $this->streamProducer = $this->createMock(StreamProducer::class);
        $this->aggregateType = $this->createMock(AggregateType::class);
        $this->aggregateCache = $this->createMock(AggregateCache::class);
        $this->aggregateReleaser = $this->createMock(AggregateEventReleaser::class);
        $this->queryRepository = $this->createMock(AggregateQueryRepository::class);
        $this->someIdentity = V4AggregateId::fromString($this->identityString);
        $this->streamName = new StreamName('operation');
    }

    public function testRetrieveAggregateFromCache(): void
    {
        $expectedAggregateRoot = AggregateRootStub::create($this->someIdentity);

        $this->aggregateCache->expects($this->once())->method('has')->with($this->someIdentity)->willReturn(true);
        $this->aggregateCache->method('get')->with($this->someIdentity)->willReturn($expectedAggregateRoot);

        $stub = $this->createAggregateRepository();

        $aggregateRoot = $stub->retrieve($this->someIdentity);

        $this->assertEquals($expectedAggregateRoot, $aggregateRoot);
    }

    public function testReconstituteAggregateAndPutInCacheWhenRetrieve(): void
    {
        $expectedAggregate = $this->createMock(AggregateRoot::class);

        $this->aggregateCache->expects($this->once())->method('has')->willReturn(false);
        $this->aggregateCache->expects($this->never())->method('get');
        $this->aggregateCache->expects($this->once())->method('put');

        $this->queryRepository
            ->expects($this->once())
            ->method('retrieve')
            ->with($this->someIdentity)
            ->willReturn($expectedAggregate);

        $repository = $this->createAggregateRepository();

        $this->assertSame($expectedAggregate, $repository->retrieve($this->someIdentity));
    }

    public function testItDoesNotPutInCacheNullAggregate(): void
    {
        $this->aggregateCache->expects($this->once())->method('has')->willReturn(false);
        $this->aggregateCache->expects($this->never())->method('get');
        $this->aggregateCache->expects($this->never())->method('put');
        $this->aggregateType->expects($this->never())->method('from');

        $this->queryRepository
            ->expects($this->once())
            ->method('retrieve')
            ->with($this->someIdentity)
            ->willReturn(null);

        $repository = $this->createAggregateRepository();

        $this->assertNull($repository->retrieve($this->someIdentity));
    }

    public function testReconstituteAggregateFromFilteredHistory(): void
    {
        $expectedAggregate = $this->createMock(AggregateRoot::class);
        $queryFilter = $this->createMock(QueryFilter::class);

        $this->aggregateCache->expects($this->never())->method('has');
        $this->aggregateCache->expects($this->never())->method('get');
        $this->aggregateCache->expects($this->never())->method('put');

        $this->queryRepository
            ->expects($this->once())
            ->method('retrieveFiltered')
            ->with($this->someIdentity, $queryFilter)
            ->willReturn($expectedAggregate);

        $repository = $this->createAggregateRepository();

        $this->assertSame($expectedAggregate, $repository->retrieveFiltered($this->someIdentity, $queryFilter));
    }

    public function testForgetAggregateWhenExceptionRaisedOnFirstCommit(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('some exception message');

        $exception = new RuntimeException('some exception message');

        $events = [SomeEvent::fromContent(['foo' => 'bar'])];

        $aggregateRoot = AggregateRootStub::create($this->someIdentity, ...$events);

        $this->aggregateType
            ->expects($this->once())
            ->method('assertAggregateIsSupported')
            ->with($aggregateRoot::class);

        $this->aggregateReleaser
            ->expects($this->once())
            ->method('releaseEvents')
            ->with($aggregateRoot)
            ->willReturn($events);

        $stream = new Stream($this->streamName, $events);

        $this->streamProducer->expects($this->once())
            ->method('toStream')
            ->with($this->someIdentity, $events)
            ->willReturn($stream);

        $this->streamProducer->expects($this->once())
            ->method('isFirstCommit')
            ->with($this->isInstanceOf(SomeEvent::class))
            ->willReturn(true);

        $this->chronicler->expects($this->once())
            ->method('firstCommit')
            ->with($stream)
            ->willThrowException($exception);

        $this->aggregateCache->expects($this->once())->method('forget')->with($this->someIdentity);

        $repository = $this->createAggregateRepository();

        $repository->store($aggregateRoot);
    }

    public function testForgetAggregateWhenExceptionRaisedOnPersist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('foo');

        $exception = new RuntimeException('foo');

        $events = [SomeEvent::fromContent(['foo' => 'bar'])];

        $aggregateRoot = AggregateRootStub::create($this->someIdentity, ...$events);

        $this->aggregateType
            ->expects($this->once())
            ->method('assertAggregateIsSupported')
            ->with($aggregateRoot::class);

        $this->aggregateReleaser
            ->expects($this->once())
            ->method('releaseEvents')
            ->with($aggregateRoot)
            ->willReturn($events);

        $stream = new Stream($this->streamName, $events);

        $this->streamProducer->expects($this->once())
            ->method('toStream')
            ->with($this->someIdentity, $events)
            ->willReturn($stream);

        $this->streamProducer->expects($this->once())
            ->method('isFirstCommit')
            ->with($this->isInstanceOf(SomeEvent::class))
            ->willReturn(false);

        $this->chronicler->expects($this->once())
            ->method('amend')
            ->with($stream)
            ->willThrowException($exception);

        $this->aggregateCache->expects($this->once())
            ->method('forget')
            ->with($this->someIdentity);

        $this->createAggregateRepository()->store($aggregateRoot);
    }

    public function testDoesNotPersistWithNoStreamEvent(): void
    {
        $aggregateRoot = AggregateRootStub::create($this->someIdentity);

        $this->aggregateReleaser
            ->expects($this->once())
            ->method('releaseEvents')
            ->with($aggregateRoot)
            ->willReturn([]);

        $this->aggregateType->expects($this->once())->method('assertAggregateIsSupported')->with($aggregateRoot::class);
        $this->streamProducer->expects($this->never())->method('toStream');
        $this->streamProducer->expects($this->never())->method('isFirstCommit');
        $this->chronicler->expects($this->never())->method('firstCommit');
        $this->chronicler->expects($this->never())->method('amend');
        $this->aggregateCache->expects($this->never())->method('forget');

        $this->createAggregateRepository()->store($aggregateRoot);
    }

    public function testFirstCommit(): void
    {
        $events = iterator_to_array($this->provideFourDomainEvents());

        $aggregateRoot = AggregateRootStub::create($this->someIdentity, ...$events);

        $this->aggregateType
            ->expects($this->once())
            ->method('assertAggregateIsSupported')
            ->with($aggregateRoot::class);

        $this->aggregateReleaser
            ->expects($this->once())
            ->method('releaseEvents')
            ->with($aggregateRoot)
            ->willReturn($events);

        $stream = new Stream($this->streamName, $events);

        $this->streamProducer->expects($this->once())
            ->method('toStream')
            ->with($this->someIdentity, $events)
            ->willReturn($stream);

        $this->streamProducer
            ->expects($this->once())
            ->method('isFirstCommit')
            ->with($this->isInstanceOf(SomeEvent::class))
            ->willReturn(true);

        $this->chronicler->expects($this->once())->method('firstCommit')->with($stream);
        $this->aggregateCache->expects($this->once())->method('put')->with($aggregateRoot);

        $this->createAggregateRepository()->store($aggregateRoot);
    }

    public function testAmend(): void
    {
        /** @var AggregateRootStub $aggregateRoot */
        $aggregateRoot = AggregateRootStub::reconstitute($this->someIdentity, $this->provideFourDomainEvents());

        $this->assertEquals(4, $aggregateRoot->version());

        $events = iterator_to_array($this->provideFourDomainEvents());

        $aggregateRoot->recordSomeEvents(...$events);

        $this->assertEquals(8, $aggregateRoot->version());

        $this->aggregateType
            ->expects($this->once())
            ->method('assertAggregateIsSupported')
            ->with($aggregateRoot::class);

        $this->aggregateReleaser
            ->expects($this->once())
            ->method('releaseEvents')
            ->with($aggregateRoot)
            ->willReturn($events);

        $stream = new Stream($this->streamName, $events);

        $this->streamProducer
            ->expects($this->once())
            ->method('toStream')
            ->with($this->someIdentity, $events)
            ->willReturn($stream);

        $this->streamProducer->expects($this->once())
            ->method('isFirstCommit')
            ->with($this->isInstanceOf(SomeEvent::class))
            ->willReturn(false);

        $this->chronicler->expects($this->once())->method('amend')->with($stream);
        $this->aggregateCache->expects($this->once())->method('put')->with($aggregateRoot);

        $this->createAggregateRepository()->store($aggregateRoot);
    }

    public function testRetrieveHistory(): void
    {
        $expectedEvents = iterator_to_array($this->provideFourDomainEvents());

        $this->queryRepository
            ->expects($this->once())
            ->method('retrieveHistory')
            ->with($this->someIdentity, null)
            ->willReturnCallback(static fn (): Generator => yield from $expectedEvents);

        $repository = $this->createAggregateRepository();

        $events = $repository->retrieveHistory($this->someIdentity, null);

        $this->assertEquals($expectedEvents, iterator_to_array($events));
    }

    public function testRetrieveHistoryWithQueryFilter(): void
    {
        $queryFilter = $this->createMock(QueryFilter::class);

        $expectedEvents = iterator_to_array($this->provideFourDomainEvents());

        $this->queryRepository
            ->expects($this->once())
            ->method('retrieveHistory')
            ->with($this->someIdentity, $queryFilter)
            ->willReturnCallback(static fn (): Generator => yield from $expectedEvents);

        $repository = $this->createAggregateRepository();

        $events = $repository->retrieveHistory($this->someIdentity, $queryFilter);

        $this->assertEquals($expectedEvents, iterator_to_array($events));
    }

    private function provideFourDomainEvents(): Generator
    {
        yield from [
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
        ];

        return 4;
    }

    private function createAggregateRepository(): AggregateRepository
    {
        return new GenericAggregateRepository(
            $this->chronicler,
            $this->streamProducer,
            $this->aggregateCache,
            $this->aggregateType,
            $this->aggregateReleaser,
            $this->queryRepository,
        );
    }
}
