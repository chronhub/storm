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
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Scheme\StreamManager;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

use function count;

#[CoversClass(LoadStreams::class)]
final class LoadStreamsTest extends UnitTestCase
{
    private Subscription|MockObject $subscription;

    private Chronicler|MockObject $chronicler;

    private ContextInterface|MockObject $context;

    private StreamManager $streamPosition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscription = $this->createMock(Subscription::class);
        $this->chronicler = $this->createMock(Chronicler::class);
        $this->context = $this->createMock(ContextInterface::class);
        $this->streamPosition = new StreamManager(new InMemoryEventStream());
    }

    public function testLoadStreamsFromQueryFilter(): void
    {
        $this->prepareStreamPosition('stream2', 0);

        $this->context
            ->expects($this->once())
            ->method('queryFilter')
            ->willReturn($this->createMock(QueryFilter::class));

        $this->chronicler->expects($this->once())->method('retrieveFiltered')->willReturn($this->provideEventStream(['name' => 'steph']));

        $loadStreams = new LoadStreams($this->chronicler);

        $iterator = $loadStreams->batch(
            $this->subscription->streamManager()->jsonSerialize(),
            $this->subscription->context()->queryFilter()
        );

        $this->assertStreamIterator($iterator, 'stream2', ['name' => 'steph']);
    }

    public function testLoadStreamsFromProjectionQueryFilter(): void
    {
        $this->prepareStreamPosition('stream1', 0);

        $projectionQueryFilter = $this->createMock(ProjectionQueryFilter::class);
        $projectionQueryFilter
            ->expects($this->once())
            ->method('setCurrentPosition')
            ->with(1);

        $this->context->expects($this->once())->method('queryFilter')->willReturn($projectionQueryFilter);

        $this->chronicler->expects($this->once())->method('retrieveFiltered')
            ->willReturn($this->provideEventStream(['foo' => 'bar'], ['baz' => 'bar']));

        $loadStreams = new LoadStreams($this->chronicler);

        $iterator = $loadStreams->batch(
            $this->subscription->streamManager()->jsonSerialize(),
            $this->subscription->context()->queryFilter()
        );

        $this->assertStreamIterator($iterator, 'stream1', ['foo' => 'bar']);

        $iterator->next();

        $this->assertStreamIterator($iterator, 'stream1', ['baz' => 'bar']);
    }

    public function testCatchStreamNotFoundAndIterateOverNextStream(): void
    {
        $this->prepareStreamPosition('stream1', 0);
        $this->prepareStreamPosition('stream2', 5);

        $projectionQueryFilter = $this->createMock(ProjectionQueryFilter::class);

        $projectionQueryFilter->expects($this->exactly(2))->method('setCurrentPosition')
            ->willReturnOnConsecutiveCalls(1, 6); // positions + 1

        $this->context->expects($this->once())->method('queryFilter')->willReturn($projectionQueryFilter);

        $this->chronicler->expects($this->exactly(2))->method('retrieveFiltered')
            ->willReturnOnConsecutiveCalls(
                $this->provideStreamNotFound(),
                $this->provideEventStream(['bar' => 'bar']),
            );

        $loadStreams = new LoadStreams($this->chronicler);

        $iterator = $loadStreams->batch(
            $this->subscription->streamManager()->jsonSerialize(),
            $this->subscription->context()->queryFilter()
        );

        $this->assertInstanceOf(MergeStreamIterator::class, $iterator);
        $this->assertSame('stream2', $iterator->streamName());
    }

    private function prepareStreamPosition(string $streamName, int $position): void
    {
        $this->streamPosition->bind($streamName, $position);

        $this->subscription->expects($this->once())->method('streamManager')->willReturn($this->streamPosition);

        $this->subscription->expects($this->once())->method('context')->willReturn($this->context);
    }

    private function provideEventStream(array ...$data): Generator
    {
        foreach ($data as $content) {
            yield SomeEvent::fromContent($content);
        }

        return count($data);
    }

    private function provideStreamNotFound(): Generator
    {
        yield throw new StreamNotFound('stream not found');
    }

    private function assertStreamIterator(
        MergeStreamIterator $iterator,
        string $expectedStreamName,
        array $expectedEventContent
    ): void {
        $this->assertSame(MergeStreamIterator::class, $iterator::class);
        $this->assertSame($expectedStreamName, $iterator->streamName());
        $this->assertEquals($expectedEventContent, $iterator->current()->toContent());
    }
}
