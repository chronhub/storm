<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Stream;

use Generator;
use Chronhub\Storm\Stream\Stream;
use Illuminate\Support\Collection;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;

#[CoversClass(Stream::class)]
final class StreamTest extends UnitTestCase
{
    private StreamName $streamName;

    protected function setUp(): void
    {
        parent::setUp();
        $this->streamName = new StreamName('some_stream_name');
    }

    #[DataProvider('provideIterableEvents')]
    #[Test]
    public function it_instantiate_stream(iterable $events): void
    {
        $stream = new Stream($this->streamName, $events);

        $this->assertSame($this->streamName, $stream->name());

        $this->assertInstanceOf(SomeEvent::class, $stream->events()->current());

        $this->assertCount(1, $events);
    }

    #[Test]
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

    public static function provideIterableEvents(): Generator
    {
        $event = SomeEvent::fromContent(['foo' => 'bar']);

        yield[[$event]];

        yield [new Collection([$event])];

        yield [new LazyCollection([$event])];
    }

    private function provideGenerator(): Generator
    {
        yield from $this->dummyEvent();

        return 1;
    }

    private static function dummyEvent(): array
    {
        return [SomeEvent::fromContent(['foo' => 'bar'])];
    }
}
