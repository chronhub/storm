<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Generator;
use RuntimeException;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Aggregate\V4AggregateId;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Message\NoOpMessageDecorator;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Stream\StreamProducer;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Aggregate\AggregateCache;
use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Aggregate\InteractWithAggregateRepository;
use Chronhub\Storm\Tests\Stubs\InteractWithAggregateRepositoryStub;
use function iterator_to_array;

#[CoversClass(InteractWithAggregateRepository::class)]
final class InteractWithAggregateRepositoryTest extends UnitTestCase
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
        $stub = $this->aggregateRepositoryStub(null);

        $this->assertEquals($this->chronicler, $stub->chronicler);
        $this->assertEquals($this->aggregateCache, $stub->aggregateCache);
        $this->assertEquals($this->streamProducer, $stub->streamProducer);
    }

    public function testRetrieveAggregateFromCache(): void
    {
        $expectedAggregateRoot = AggregateRootStub::create($this->someIdentity);

        $this->aggregateCache->expects($this->once())->method('has')->with($this->someIdentity)->willReturn(true);
        $this->aggregateCache->method('get')->with($this->someIdentity)->willReturn($expectedAggregateRoot);

        $stub = $this->aggregateRepositoryStub(null);

        $aggregateRoot = $stub->retrieve($this->someIdentity);

        $this->assertEquals($expectedAggregateRoot, $aggregateRoot);
    }

    #[Test]
    public function testReconstituteAggregateAndPutInCacheWhenCacheDoesNotHaveIt(): void
    {
        $expectedAggregateRoot = AggregateRootStub::create($this->someIdentity);

        $this->aggregateCache->expects($this->once())->method('has')->willReturn(false);
        $this->aggregateCache->expects($this->never())->method('get');
        $this->aggregateCache->expects($this->once())->method('put');

        $stub = $this->aggregateRepositoryStub(null);
        $stub->withReconstituteAggregateRoot($expectedAggregateRoot);

        $aggregateRoot = $stub->retrieve($this->someIdentity);

        $this->assertEquals($expectedAggregateRoot, $aggregateRoot);
    }

    #[Test]
    public function testItDoesNotPutInCacheNullAggregate(): void
    {
        $this->aggregateCache->expects($this->once())->method('has')->willReturn(false);
        $this->aggregateCache->expects($this->never())->method('get');
        $this->aggregateCache->expects($this->never())->method('put');

        $stub = $this->aggregateRepositoryStub(null);
        $stub->withReconstituteAggregateRoot(null);

        $aggregateRoot = $stub->retrieve($this->someIdentity);

        $this->assertNull($aggregateRoot);
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

        $stub = $this->aggregateRepositoryStub(null);

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

        $stub = $this->aggregateRepositoryStub(null);

        $stub->store($aggregateRoot);
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

        $stub = $this->aggregateRepositoryStub(null);

        $stub->store($aggregateRoot);
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

        $stub = $this->aggregateRepositoryStub($messageDecorator);

        $stub->store($aggregateRoot);
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

        $stub = $this->aggregateRepositoryStub($messageDecorator);

        $stub->store($aggregateRoot);
    }

    public static function provideMessageDecoratorOrNull(): Generator
    {
        yield[null];

        yield[
            new class implements MessageDecorator
            {
                public function decorate(Message $message): Message
                {
                    return $message->withHeader('some', 'header');
                }
            },
        ];
    }

    public function provideFourDomainEvents(): Generator
    {
        yield from [
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
            SomeEvent::fromContent(['foo' => 'bar']),
        ];

        return 4;
    }

    private function aggregateRepositoryStub(?MessageDecorator $messageDecorator): InteractWithAggregateRepositoryStub
    {
        return new InteractWithAggregateRepositoryStub(
            $this->chronicler,
            $this->streamProducer,
            $this->aggregateCache,
            $this->aggregateType,
            $messageDecorator ?? new NoOpMessageDecorator()
        );
    }
}
