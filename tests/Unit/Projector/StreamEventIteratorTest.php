<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Projector\Iterator\StreamEventIterator;

final class StreamEventIteratorTest extends UnitTestCase
{
    private array $events = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->events = [
            SomeEvent::fromContent(['foo' => 'bar'])->withHeader(
                EventHeader::INTERNAL_POSITION, 1
            ),

            SomeEvent::fromContent(['foo' => 'baz'])->withHeader(
                EventHeader::INTERNAL_POSITION, 2
            ),
        ];
    }

    #[Test]
    public function it_can_be_constructed_with_events_generator(): void
    {
        $iterator = new StreamEventIterator($this->provideEvents());

        $this->assertTrue($iterator->valid());

        $this->assertEquals($this->events[0], $iterator->current());
        $this->assertEquals($iterator->key(), $this->events[0]->header(EventHeader::INTERNAL_POSITION));

        $iterator->next();

        $this->assertEquals($this->events[1], $iterator->current());
        $this->assertEquals($iterator->key(), $this->events[1]->header(EventHeader::INTERNAL_POSITION));

        $iterator->next();

        $this->assertFalse($iterator->key());
        $this->assertNull($iterator->current());
    }

    #[Test]
    public function it_does_not_catch_stream_not_found_exception_with_empty_iterator(): void
    {
        $this->expectException(StreamNotFound::class);

        new StreamEventIterator($this->provideStreamNotFoundWhileIterating());
    }

    public function provideEvents(): Generator
    {
        yield from $this->events;
    }

    public function provideInvalidEvents(): Generator
    {
        yield from [SomeEvent::fromContent(['foo' => 'bar'])
            ->withHeader(EventHeader::INTERNAL_POSITION, 0), ];
    }

    public function provideInvalidEvents2(): Generator
    {
        yield from [SomeEvent::fromContent(['foo' => 'bar'])];
    }

    public function provideStreamNotFoundWhileIterating(): Generator
    {
        yield from (new LazyCollection())->whenEmpty(function (): never {
            throw new StreamNotFound('stream not found');
        });
    }
}
