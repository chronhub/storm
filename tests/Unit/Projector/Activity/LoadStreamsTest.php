<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Activity\LoadStreams;
use Chronhub\Storm\Projector\Iterator\SortStreamIterator;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(LoadStreams::class)]
class LoadStreamsTest extends UnitTestCase
{
    private Subscription|MockObject $subscription;

    private Chronicler|MockObject $chronicler;

    private ContextInterface|MockObject $context;

    private StreamPosition $streamPosition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscription = $this->createMock(Subscription::class);
        $this->chronicler = $this->createMock(Chronicler::class);
        $this->context = $this->createMock(ContextInterface::class);
        $this->streamPosition = new StreamPosition(new InMemoryEventStream());
    }

    public function testLoadStreamsFromQueryFilter(): void
    {
        $this->streamPosition->bind('foo', 0);

        $this->subscription
            ->expects($this->once())
            ->method('streamPosition')
            ->willReturn($this->streamPosition);

        $this->subscription
            ->expects($this->once())
            ->method('context')
            ->willReturn($this->context);

        $this->context
            ->expects($this->once())
            ->method('queryFilter')
            ->willReturn($this->createMock(QueryFilter::class));

        $this->chronicler
            ->expects($this->once())
            ->method('retrieveFiltered')
            ->willReturnCallback(function (): Generator {
                yield SomeEvent::fromContent(['foo' => 'bar']);

                return 1;
            });

        $loadStreams = new LoadStreams($this->chronicler);

        $iterator = $loadStreams->loadFrom($this->subscription);

        $this->assertSame(SortStreamIterator::class, $iterator::class);
        $this->assertSame('foo', $iterator->streamName());
        $this->assertEquals(['foo' => 'bar'], $iterator->current()->toContent());
    }

    public function testLoadStreamsFromProjectionQueryFilter(): void
    {
        $this->streamPosition->bind('foo', 0);

        $this->subscription
            ->expects($this->once())
            ->method('streamPosition')
            ->willReturn($this->streamPosition);

        $this->subscription
            ->expects($this->once())
            ->method('context')
            ->willReturn($this->context);

        $projectionQueryFilter = $this->createMock(ProjectionQueryFilter::class);
        $projectionQueryFilter
            ->expects($this->once())
            ->method('setCurrentPosition')
            ->with(1);

        $this->context
            ->expects($this->once())
            ->method('queryFilter')
            ->willReturn($projectionQueryFilter);

        $this->chronicler
            ->expects($this->once())
            ->method('retrieveFiltered')
            ->willReturnCallback(function (): Generator {
                yield SomeEvent::fromContent(['foo' => 'bar']);
                yield SomeEvent::fromContent(['baz' => 'bar']);

                return 2;
            });

        $loadStreams = new LoadStreams($this->chronicler);

        $iterator = $loadStreams->loadFrom($this->subscription);

        $this->assertSame(SortStreamIterator::class, $iterator::class);
        $this->assertSame('foo', $iterator->streamName());
        $this->assertEquals(['foo' => 'bar'], $iterator->current()->toContent());

        $iterator->next();

        $this->assertEquals(['baz' => 'bar'], $iterator->current()->toContent());
    }

    public function testCatchStreamNotFoundAndIterateOverNextStream(): void
    {
        $this->streamPosition->bind('foo', 0);
        $this->streamPosition->bind('bar', 5);

        $this->subscription
            ->expects($this->once())
            ->method('streamPosition')
            ->willReturn($this->streamPosition);

        $this->subscription
            ->expects($this->once())
            ->method('context')
            ->willReturn($this->context);

        $projectionQueryFilter = $this->createMock(ProjectionQueryFilter::class);
        $projectionQueryFilter
            ->expects($this->exactly(2))
            ->method('setCurrentPosition')
            ->will($this->onConsecutiveCalls(1, 6)); //fixMe and complete with different values

        $this->context
            ->expects($this->once())
            ->method('queryFilter')
            ->willReturn($projectionQueryFilter);

        $this->chronicler
            ->expects($this->exactly(2))
            ->method('retrieveFiltered')
            ->willReturnOnConsecutiveCalls(
               $this->returnCallback(fn () => $this->provideException()),
               $this->returnCallback(fn () => $this->provideEvent()),
            );

        $loadStreams = new LoadStreams($this->chronicler);

        $iterator = $loadStreams->loadFrom($this->subscription);

        $this->assertInstanceOf(SortStreamIterator::class, $iterator);
        $this->assertSame('bar', $iterator->streamName());
    }

    private function provideEvent(): Generator
    {
        yield SomeEvent::fromContent(['foo' => 'bar']);

        return 1;
    }

    private function provideException(): Generator
    {
        throw StreamNotFound::withStreamName(new StreamName('foo'));
    }
}
