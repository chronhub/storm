<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Generator;
use RuntimeException;
use Prophecy\Argument;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Stream\StreamName;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Message\NoOpMessageDecorator;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Stream\StreamProducer;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Aggregate\AggregateCache;
use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Tests\Stubs\InteractWithAggregateRepositoryStub;
use function iterator_to_array;

final class InteractWithAggregateRepositoryTest extends ProphecyTestCase
{
    private Chronicler|ObjectProphecy $chronicler;

    private StreamProducer|ObjectProphecy $streamProducer;

    private AggregateType|ObjectProphecy $aggregateType;

    private AggregateCache|ObjectProphecy $aggregateCache;

    private AggregateIdentity|ObjectProphecy $someIdentity;

    private StreamName $streamName;

    private string $identityString = '9ef864f7-43e2-48c8-9944-639a2d927a06';

    public function setUp(): void
    {
        parent::setUp();

        $this->chronicler = $this->prophesize(Chronicler::class);
        $this->streamProducer = $this->prophesize(StreamProducer::class);
        $this->aggregateType = $this->prophesize(AggregateType::class);
        $this->aggregateCache = $this->prophesize(AggregateCache::class);
        $this->someIdentity = V4AggregateId::fromString($this->identityString);
        $this->streamName = new StreamName('operation');
    }

    /**
     * @test
     */
    public function it_assert_stub_accessor(): void
    {
        $stub = $this->aggregateRepositoryStub(null);

        $this->assertEquals($this->chronicler->reveal(), $stub->chronicler);
        $this->assertEquals($this->aggregateCache->reveal(), $stub->aggregateCache);
        $this->assertEquals($this->streamProducer->reveal(), $stub->streamProducer);
    }

    /**
     * @test
     */
    public function it_retrieve_aggregate_from_cache(): void
    {
        $expectedAggregateRoot = AggregateRootStub::create($this->someIdentity);

        $this->aggregateCache->has($this->someIdentity)->willReturn(true)->shouldBeCalledOnce();
        $this->aggregateCache->get($this->someIdentity)->willReturn($expectedAggregateRoot);

        $stub = $this->aggregateRepositoryStub(null);

        $aggregateRoot = $stub->retrieve($this->someIdentity);

        $this->assertEquals($expectedAggregateRoot, $aggregateRoot);
    }

    /**
     * @test
     */
    public function it_reconstitute_aggregate_if_aggregate_does_not_exist_already_in_cache_and_put_in_cache_(): void
    {
        $expectedAggregateRoot = AggregateRootStub::create($this->someIdentity);

        $this->aggregateCache->has($this->someIdentity)->willReturn(false)->shouldBeCalledOnce();
        $this->aggregateCache->get($this->someIdentity)->shouldNotBeCalled();
        $this->aggregateCache->put($expectedAggregateRoot)->shouldBeCalledOnce();

        $stub = $this->aggregateRepositoryStub(null);
        $stub->withReconstituteAggregateRoot($expectedAggregateRoot);

        $aggregateRoot = $stub->retrieve($this->someIdentity);

        $this->assertEquals($expectedAggregateRoot, $aggregateRoot);
    }

    /**
     * @test
     */
    public function it_does_not_put_in_cache_if_reconstitute_aggregate_return_null_aggregate(): void
    {
        $expectedAggregateRoot = AggregateRootStub::create($this->someIdentity);

        $this->aggregateCache->has($this->someIdentity)->willReturn(false)->shouldBeCalledOnce();
        $this->aggregateCache->get($this->someIdentity)->shouldNotBeCalled();
        $this->aggregateCache->put($expectedAggregateRoot)->shouldNotBeCalled();

        $stub = $this->aggregateRepositoryStub(null);
        $stub->withReconstituteAggregateRoot(null);

        $aggregateRoot = $stub->retrieve($this->someIdentity);

        $this->assertNull($aggregateRoot);
    }

    /**
     * @test
     */
    public function it_forget_aggregate_from_cache_if_persist_first_commit_raise_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('some exception message');

        $exception = new RuntimeException('some exception message');

        $events = [SomeEvent::fromContent(['foo' => 'bar'])];

        $aggregateRoot = AggregateRootStub::create($this->someIdentity, ...$events);

        $this->aggregateType->isSupported($aggregateRoot::class)->shouldBeCalledOnce();

        $stream = new Stream($this->streamName, $events);

        $this->streamProducer
            ->toStream($this->someIdentity, Argument::type('array'))
            ->willReturn($stream)
            ->shouldBeCalledOnce();

        $this->streamProducer->isFirstCommit(Argument::type(SomeEvent::class))->willReturn(true)->shouldBeCalledOnce();

        $this->chronicler->firstCommit($stream)
            ->willThrow($exception)
            ->shouldBeCalledOnce();

        $this->aggregateCache->forget($this->someIdentity)->shouldBeCalledOnce();

        $stub = $this->aggregateRepositoryStub(null);

        $stub->store($aggregateRoot);
    }

    /**
     * @test
     */
    public function it_forget_aggregate_from_cache_if_persist_raise_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('foo');

        $exception = new RuntimeException('foo');

        $events = [SomeEvent::fromContent(['foo' => 'bar'])];

        $aggregateRoot = AggregateRootStub::create($this->someIdentity, ...$events);

        $this->aggregateType->isSupported($aggregateRoot::class)->shouldBeCalledOnce();

        $stream = new Stream($this->streamName, $events);

        $this->streamProducer
            ->toStream($this->someIdentity, Argument::type('array'))
            ->willReturn($stream)
            ->shouldBeCalledOnce();

        $this->streamProducer
            ->isFirstCommit(Argument::type(DomainEvent::class))
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $this->chronicler->amend($stream)
            ->willThrow($exception)
            ->shouldBeCalledOnce();

        $this->aggregateCache
            ->forget($this->someIdentity)
            ->shouldBeCalledOnce();

        $stub = $this->aggregateRepositoryStub(null);

        $stub->store($aggregateRoot);
    }

    /**
     * @test
     */
    public function it_does_not_persist_aggregate_with_no_event_to_release(): void
    {
        $events = [];

        $aggregateRoot = AggregateRootStub::create($this->someIdentity, ...$events);

        $this->aggregateType->isSupported($aggregateRoot::class)->shouldBeCalledOnce();

        $stream = new Stream($this->streamName, $events);

        $this->streamProducer->toStream($this->someIdentity, $events)->shouldNotBeCalled();
        $this->streamProducer->isFirstCommit($this->prophesize(DomainEvent::class)->reveal())->shouldNotBeCalled();
        $this->chronicler->firstCommit($stream)->shouldNotBeCalled();
        $this->chronicler->amend($stream)->shouldNotBeCalled();
        $this->aggregateCache->forget($this->someIdentity)->shouldNotBeCalled();

        $stub = $this->aggregateRepositoryStub(null);

        $stub->store($aggregateRoot);
    }

    /**
     * @test
     *
     * @dataProvider provideMessageDecoratorOrNull
     */
    public function it_persists_aggregate_root_with_first_commit_and_decorate_domain_events(?MessageDecorator $messageDecorator): void
    {
        $events = iterator_to_array($this->provideFourDomainEvents());

        $aggregateRoot = AggregateRootStub::create($this->someIdentity, ...$events);

        $this->aggregateType->isSupported($aggregateRoot::class)->shouldBeCalledOnce();

        $stream = new Stream($this->streamName, $events);

        $this->streamProducer
            ->toStream($this->someIdentity, Argument::that(function (array $events) use ($messageDecorator): array {
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

                $this->assertEquals(4, $position);

                return $events;
            }))
            ->willReturn($stream)
            ->shouldBeCalledOnce();

        $this->streamProducer->isFirstCommit(Argument::type(SomeEvent::class))->willReturn(true)->shouldBeCalledOnce();
        $this->chronicler->firstCommit($stream)->shouldBeCalledOnce();
        $this->aggregateCache->put($aggregateRoot)->shouldBeCalledOnce();

        $stub = $this->aggregateRepositoryStub($messageDecorator);

        $stub->store($aggregateRoot);
    }

    /**
     * @test
     *
     * @dataProvider provideMessageDecoratorOrNull
     */
    public function it_persists_aggregate_root_and_decorate_domain_events(?MessageDecorator $messageDecorator): void
    {
        /** @var AggregateRootStub $aggregateRoot */
        $aggregateRoot = AggregateRootStub::reconstitute($this->someIdentity, $this->provideFourDomainEvents());

        $this->assertEquals(4, $aggregateRoot->version());

        $events = iterator_to_array($this->provideFourDomainEvents());

        $aggregateRoot->recordSomeEvents(...$events);

        $this->assertEquals(8, $aggregateRoot->version());

        $this->aggregateType->isSupported($aggregateRoot::class)->shouldBeCalledOnce();

        $stream = new Stream($this->streamName, $events);

        $this->streamProducer
            ->toStream($this->someIdentity, Argument::that(function (array $events) use ($messageDecorator): array {
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

                $this->assertEquals(8, $position);

                return $events;
            }))
            ->willReturn($stream)
            ->shouldBeCalledOnce();

        $this->streamProducer->isFirstCommit(Argument::type(SomeEvent::class))->willReturn(false)->shouldBeCalledOnce();
        $this->chronicler->amend($stream)->shouldBeCalledOnce();
        $this->aggregateCache->put($aggregateRoot)->shouldBeCalledOnce();

        $stub = $this->aggregateRepositoryStub($messageDecorator);

        $stub->store($aggregateRoot);
    }

    public function provideMessageDecoratorOrNull(): Generator
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
            $this->chronicler->reveal(),
            $this->streamProducer->reveal(),
            $this->aggregateCache->reveal(),
            $this->aggregateType->reveal(),
            $messageDecorator ?? new NoOpMessageDecorator()
        );
    }
}
