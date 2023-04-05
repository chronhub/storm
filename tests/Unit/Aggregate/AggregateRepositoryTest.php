<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Chronhub\Storm\Aggregate\AbstractAggregateRepository;
use Chronhub\Storm\Aggregate\AggregateRepository;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Aggregate\AggregateCache;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateRepository as Repository;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Contracts\Stream\StreamProducer;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Message\NoOpMessageDecorator;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use function iterator_to_array;

#[CoversClass(AggregateRepository::class)]
#[CoversClass(AbstractAggregateRepository::class)]
final class AggregateRepositoryTest extends UnitTestCase
{
    private Chronicler|MockObject $chronicler;

    private StreamProducer|MockObject $streamProducer;

    private AggregateType|MockObject $aggregateType;

    private AggregateCache|MockObject $aggregateCache;

    private AggregateIdentity|MockObject $someIdentity;

    private StreamName $streamName;

    private string $identityString = '9ef864f7-43e2-48c8-9944-639a2d927a06';

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
        $this->someIdentity = V4AggregateId::fromString($this->identityString);
        $this->streamName = new StreamName('operation');
    }

    public function testInstance(): void
    {
        $repository = $this->createAggregateRepository();

        $this->assertSame($this->chronicler, $repository->chronicler);
        $this->assertSame($this->streamProducer, $repository->streamProducer);
        $this->assertSame($this->aggregateCache, $repository->aggregateCache);
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

    public function testReconstituteAggregateAndPutInCacheWhenCacheDoesNotHaveIt(): void
    {
        $history = iterator_to_array($this->provideFourDomainEvents());

        $this->aggregateCache->expects($this->once())->method('has')->willReturn(false);
        $this->aggregateCache->expects($this->never())->method('get');
        $this->aggregateCache->expects($this->once())->method('put');

        $this->aggregateType
            ->expects($this->once())
            ->method('from')
            ->with($history[0])
            ->willReturn(AggregateRootStub::class);

        $this->streamProducer
            ->expects($this->once())
            ->method('toStreamName')
            ->with($this->someIdentity)
            ->willReturn($this->streamName);

        $this->chronicler
            ->expects($this->once())
            ->method('retrieveAll')
            ->with($this->streamName, $this->someIdentity)
            ->willReturnCallback(fn (): Generator => $this->provideFourDomainEvents());

        $stub = $this->createAggregateRepository();

        $aggregateRoot = $stub->retrieve($this->someIdentity);

        $this->assertInstanceOf(AggregateRoot::class, $aggregateRoot);
        $this->assertInstanceOf(AggregateRootStub::class, $aggregateRoot);
        $this->assertEquals(4, $aggregateRoot->version());
    }

    public function testItDoesNotPutInCacheNullAggregate(): void
    {
        $this->aggregateCache->expects($this->once())->method('has')->willReturn(false);
        $this->aggregateCache->expects($this->never())->method('get');
        $this->aggregateCache->expects($this->never())->method('put');

        $this->aggregateType->expects($this->never())->method('from');

        $this->streamProducer
            ->expects($this->once())
            ->method('toStreamName')
            ->with($this->someIdentity)
            ->willReturn($this->streamName);

        $this->chronicler
            ->expects($this->once())
            ->method('retrieveAll')
            ->with($this->streamName, $this->someIdentity)
            ->willReturnCallback(fn (): Generator => yield from []);

        $stub = $this->createAggregateRepository();

        $aggregateRoot = $stub->retrieve($this->someIdentity);

        $this->assertNull($aggregateRoot);
    }

    public function testReconstituteAggregateFromFilteredHistory(): void
    {
        $this->aggregateCache->expects($this->never())->method('has');
        $this->aggregateCache->expects($this->never())->method('get');
        $this->aggregateCache->expects($this->never())->method('put');

        $events = iterator_to_array($this->provideFourDomainEvents());

        $this->streamProducer->expects($this->once())
            ->method('toStreamName')
            ->with($this->someIdentity)
            ->willReturn($this->streamName);

        $this->aggregateType->expects($this->once())
            ->method('from')
            ->with($events[0])
            ->willReturn(AggregateRootStub::class);

        $queryFilter = $this->createMock(QueryFilter::class);

        $this->chronicler->expects($this->once())
            ->method('retrieveFiltered')
            ->with($this->streamName, $queryFilter)
            ->will($this->returnCallback(function () use ($events) {
                yield $events[0];
                yield $events[1];

                return 2;
            }));

        $stub = $this->createAggregateRepository();

        $aggregateRoot = $stub->retrieveFiltered($this->someIdentity, $queryFilter);

        $this->assertInstanceOf(AggregateRootStub::class, $aggregateRoot);
        $this->assertEquals(2, $aggregateRoot->version());
    }

    public function testReturnNullAggregateWhenStreamNotFoundIsRaised(): void
    {
        $this->streamProducer->expects($this->once())
            ->method('toStreamName')
            ->with($this->someIdentity)
            ->willReturn($this->streamName);

        $this->aggregateType->expects($this->never())->method('from');

        $this->chronicler->expects($this->once())
            ->method('retrieveAll')
            ->with($this->streamName, $this->someIdentity)
            ->will($this->throwException(StreamNotFound::withStreamName($this->streamName)));

        $stub = $this->createAggregateRepository();

        $this->assertNull($stub->retrieve($this->someIdentity));
    }

    public function testReturnNullAggregateWhenNoStreamEventReturnIsRaised(): void
    {
        $this->streamProducer->expects($this->once())
            ->method('toStreamName')
            ->with($this->someIdentity)
            ->willReturn($this->streamName);

        $this->aggregateType->expects($this->never())->method('from');

        $this->chronicler->expects($this->once())
            ->method('retrieveAll')
            ->with($this->streamName, $this->someIdentity)
            ->will($this->throwException(NoStreamEventReturn::withStreamName($this->streamName)));

        $stub = $this->createAggregateRepository();

        $this->assertNull($stub->retrieve($this->someIdentity));
    }

    public function testForgetAggregateWhenExceptionRaisedOnFirstCommit(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('some exception message');

        $exception = new RuntimeException('some exception message');

        $events = [SomeEvent::fromContent(['foo' => 'bar'])];

        $aggregateRoot = AggregateRootStub::create($this->someIdentity, ...$events);

        $this->aggregateType->expects($this->once())->method('assertAggregateIsSupported')->with($aggregateRoot::class);

        $stream = new Stream($this->streamName, $events);

        $this->streamProducer->expects($this->once())
            ->method('toStream')
            ->with($this->someIdentity, $this->isType('array'))
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

        $stub = $this->createAggregateRepository();

        $stub->store($aggregateRoot);
    }

    public function testForgetAggregateWhenExceptionRaisedOnPersist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('foo');

        $exception = new RuntimeException('foo');

        $events = [SomeEvent::fromContent(['foo' => 'bar'])];

        $aggregateRoot = AggregateRootStub::create($this->someIdentity, ...$events);

        $this->aggregateType->expects($this->once())
            ->method('assertAggregateIsSupported')
            ->with($aggregateRoot::class);

        $stream = new Stream($this->streamName, $events);

        $this->streamProducer->expects($this->once())
            ->method('toStream')
            ->with($this->someIdentity, $this->isType('array'))
            ->willReturn($stream);

        $this->streamProducer->expects($this->once())
            ->method('isFirstCommit')
            ->with($this->isInstanceOf(DomainEvent::class))
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
        $events = [];

        $aggregateRoot = AggregateRootStub::create($this->someIdentity, ...$events);

        $this->aggregateType->expects($this->once())->method('assertAggregateIsSupported')->with($aggregateRoot::class);

        $this->streamProducer->expects($this->never())->method('toStream');
        $this->streamProducer->expects($this->never())->method('isFirstCommit');
        $this->chronicler->expects($this->never())->method('firstCommit');
        $this->chronicler->expects($this->never())->method('amend');
        $this->aggregateCache->expects($this->never())->method('forget');

        $this->createAggregateRepository()->store($aggregateRoot);
    }

    #[DataProvider('provideMessageDecoratorOrNull')]
    public function testFirstCommitAggregateWithDecoratedStreamEventHeaders(?MessageDecorator $messageDecorator): void
    {
        $events = iterator_to_array($this->provideFourDomainEvents());

        $aggregateRoot = AggregateRootStub::create($this->someIdentity, ...$events);

        $this->aggregateType->expects($this->once())->method('assertAggregateIsSupported')->with($aggregateRoot::class);

        $stream = new Stream($this->streamName, $events);

        $this->streamProducer->expects($this->once())
            ->method('toStream')
            ->with($this->someIdentity, $this->callback(function (array $events) use ($messageDecorator): bool {
                $position = 0;

                foreach ($events as $event) {
                    $eventHeaders = $event->headers();

                    $expectedHeaders = [
                        EventHeader::AGGREGATE_ID => $this->identityString,
                        EventHeader::AGGREGATE_ID_TYPE => V4AggregateId::class,
                        EventHeader::AGGREGATE_TYPE => AggregateRootStub::class,
                        EventHeader::AGGREGATE_VERSION => $position + 1,
                    ];

                    if ($messageDecorator) {
                        $expectedHeaders['some'] = 'header';
                    }

                    $this->assertEquals($expectedHeaders, $eventHeaders);

                    $position++;
                }

                return true;
            }))
            ->willReturn($stream);

        $this->streamProducer->expects($this->once())
            ->method('isFirstCommit')
            ->with($this->isInstanceOf(SomeEvent::class))
            ->willReturn(true);

        $this->chronicler->expects($this->once())->method('firstCommit')->with($stream);
        $this->aggregateCache->expects($this->once())->method('put')->with($aggregateRoot);

        $this->createAggregateRepository($messageDecorator)->store($aggregateRoot);
    }

    #[DataProvider('provideMessageDecoratorOrNull')]
    public function testPersistAggregateWithDecoratedStreamEventHeaders(?MessageDecorator $messageDecorator): void
    {
        /** @var AggregateRootStub $aggregateRoot */
        $aggregateRoot = AggregateRootStub::reconstitute($this->someIdentity, $this->provideFourDomainEvents());

        $this->assertEquals(4, $aggregateRoot->version());

        $events = iterator_to_array($this->provideFourDomainEvents());

        $aggregateRoot->recordSomeEvents(...$events);

        $this->assertEquals(8, $aggregateRoot->version());

        $this->aggregateType->expects($this->once())->method('assertAggregateIsSupported')->with($aggregateRoot::class);

        $stream = new Stream($this->streamName, $events);

        $this->streamProducer
            ->expects($this->once())
            ->method('toStream')
            ->with($this->someIdentity, $this->callback(function (array $events) use ($messageDecorator): bool {
                $position = 4;

                foreach ($events as $event) {
                    $eventHeaders = $event->headers();

                    $expectedHeaders = [
                        EventHeader::AGGREGATE_ID => $this->identityString,
                        EventHeader::AGGREGATE_ID_TYPE => V4AggregateId::class,
                        EventHeader::AGGREGATE_TYPE => AggregateRootStub::class,
                        EventHeader::AGGREGATE_VERSION => $position + 1,
                    ];

                    if ($messageDecorator) {
                        $expectedHeaders['some'] = 'header';
                    }

                    $this->assertEquals($expectedHeaders, $eventHeaders);

                    $position++;
                }

                return true;
            }))
            ->willReturn($stream);

        $this->streamProducer->expects($this->once())
            ->method('isFirstCommit')
            ->with($this->isInstanceOf(SomeEvent::class))
            ->willReturn(false);

        $this->chronicler->expects($this->once())->method('amend')->with($stream);
        $this->aggregateCache->expects($this->once())->method('put')->with($aggregateRoot);

        $this->createAggregateRepository($messageDecorator)->store($aggregateRoot);
    }

    public static function provideMessageDecoratorOrNull(): Generator
    {
        yield [null];

        yield [
            new class implements MessageDecorator
            {
                public function decorate(Message $message): Message
                {
                    return $message->withHeader('some', 'header');
                }
            },
        ];
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

    private function createAggregateRepository(?MessageDecorator $messageDecorator = null): Repository
    {
        return new AggregateRepository(
            $this->chronicler,
            $this->streamProducer,
            $this->aggregateCache,
            $this->aggregateType,
            $messageDecorator ?? new NoOpMessageDecorator()
        );
    }
}
