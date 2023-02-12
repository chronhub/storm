<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Stream;

use Generator;
use Chronhub\Storm\Stream\Stream;
use Illuminate\Support\Collection;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use Illuminate\Support\LazyCollection;
use Chronhub\Storm\Tests\Double\SomeEvent;

final class StreamTest extends UnitTestCase
{
    private StreamName $streamName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->streamName = new StreamName('some_stream_name');
    }

    /**
     * @test
     *
     * @dataProvider provideIterableEvents
     */
    public function it_instantiate_stream(iterable $events): void
    {
        $stream = new Stream($this->streamName, $events);

        $this->assertSame($this->streamName, $stream->name());

        $this->assertInstanceOf(SomeEvent::class, $stream->events()->current());

        $this->assertCount(1, $events);
    }

    /**
     * @test
     */
    public function it_instantiate_stream_with_generator(): void
    {
        $events = $this->provideGenerator();
        $stream = new Stream($this->streamName, $events);

        $this->assertSame($this->streamName, $stream->name());

        foreach ($stream->events() as $event) {
            $this->assertInstanceOf(SomeEvent::class, $event);
        }

        $this->assertEquals(1, $events->getReturn());
    }

    public function provideIterableEvents(): Generator
    {
        yield[$this->dummyEvent()];

        yield [new Collection($this->dummyEvent())];

        yield [new LazyCollection($this->dummyEvent())];
    }

    private function provideGenerator(): Generator
    {
        yield from $this->dummyEvent();

        return 1;
    }

    private function dummyEvent(): array
    {
        return [SomeEvent::fromContent(['foo' => 'bar'])];
    }
}
