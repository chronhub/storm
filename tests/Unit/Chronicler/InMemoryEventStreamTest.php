<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(InMemoryEventStream::class)]
final class InMemoryEventStreamTest extends UnitTestCase
{
    #[Test]
    public function it_create_new_event_stream(): void
    {
        $eventStream = new InMemoryEventStream();

        $this->assertFalse($eventStream->hasRealStreamName('balance'));

        $created = $eventStream->createStream('balance', null);

        $this->assertTrue($created);
        $this->assertTrue($eventStream->hasRealStreamName('balance'));
    }

    #[Test]
    public function it_return_false_when_event_stream_already_exists_on_create(): void
    {
        $eventStream = new InMemoryEventStream();

        $this->assertFalse($eventStream->hasRealStreamName('balance'));

        $created = $eventStream->createStream('balance', null);

        $this->assertTrue($created);

        $this->assertFalse($eventStream->createStream('balance', null));
        $this->assertFalse($eventStream->createStream('balance', null, 'operation'));
    }

    #[Test]
    public function it_create_new_event_stream_with_category(): void
    {
        $eventStream = new InMemoryEventStream();

        $created = $eventStream->createStream('add', null, 'operation');

        $this->assertTrue($created);
        $this->assertTrue($eventStream->hasRealStreamName('add'));

        $this->assertEquals(['add'], $eventStream->filterByCategories(['operation']));
    }

    #[Test]
    public function it_filter_by_event_streams_by_string_or_instance_of_stream_name(): void
    {
        $eventStream = new InMemoryEventStream();

        $eventStream->createStream('subtract', null);
        $eventStream->createStream('add', null);

        $this->assertEquals(
            ['subtract', 'add'],
            $eventStream->filterByStreams([new StreamName('subtract'), 'add'])
        );

        $this->assertEquals(
            ['subtract', 'add'],
            $eventStream->filterByStreams(['subtract', new StreamName('add'), ' balance'])
        );
    }

    #[Test]
    public function it_filter_all_streams_without_internal(): void
    {
        $eventStream = new InMemoryEventStream();

        $eventStream->createStream('subtract', null);
        $eventStream->createStream('add', null);

        $this->assertEquals(['subtract', 'add'], $eventStream->allWithoutInternal());

        $eventStream->createStream('$all', null);

        $this->assertEquals(['subtract', 'add'], $eventStream->allWithoutInternal());
    }

    #[Test]
    public function it_delete_event_stream(): void
    {
        $eventStream = new InMemoryEventStream();

        $this->assertFalse($eventStream->hasRealStreamName('balance'));
        $this->assertFalse($eventStream->deleteStream('balance'));

        $created = $eventStream->createStream('balance', '');

        $this->assertTrue($created);
        $this->assertTrue($eventStream->hasRealStreamName('balance'));

        $this->assertTrue($eventStream->deleteStream('balance'));
        $this->assertFalse($eventStream->hasRealStreamName('balance'));
    }
}
