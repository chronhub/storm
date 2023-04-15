<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InMemoryEventStream::class)]
final class InMemoryEventStreamTest extends UnitTestCase
{
    public function testCreateStream(): void
    {
        $eventStream = new InMemoryEventStream();

        $this->assertFalse($eventStream->hasRealStreamName('balance'));

        $created = $eventStream->createStream('balance', null);

        $this->assertTrue($created);
        $this->assertTrue($eventStream->hasRealStreamName('balance'));
    }

    public function testReturnFalseWhenStreamAlreadyExists(): void
    {
        $eventStream = new InMemoryEventStream();

        $this->assertFalse($eventStream->hasRealStreamName('balance'));

        $created = $eventStream->createStream('balance', null);

        $this->assertTrue($created);

        $this->assertFalse($eventStream->createStream('balance', null));
        $this->assertFalse($eventStream->createStream('balance', null, 'operation'));
    }

    public function testCreateCategory(): void
    {
        $eventStream = new InMemoryEventStream();

        $created = $eventStream->createStream('add', null, 'operation');

        $this->assertTrue($created);
        $this->assertTrue($eventStream->hasRealStreamName('add'));

        $this->assertEquals(['add'], $eventStream->filterByAscendantCategories(['operation']));
    }

    public function testFilterByAscendantStreamNames(): void
    {
        $eventStream = new InMemoryEventStream();

        $eventStream->createStream('subtract', null);
        $eventStream->createStream('add', null);

        $this->assertEquals(
            ['add', 'subtract'],
            $eventStream->filterByAscendantStreams([new StreamName('subtract'), 'add'])
        );

        $this->assertEquals(
            ['add', 'subtract'],
            $eventStream->filterByAscendantStreams(['subtract', new StreamName('add'), ' balance', 'foo'])
        );
    }

    public function testFilterCategoriesByAscendantStreamNames(): void
    {
        $eventStream = new InMemoryEventStream();

        $eventStream->createStream('subtract', null, 'op');
        $eventStream->createStream('add', null, 'op');
        $eventStream->createStream('divide', null, 'op');

        $this->assertEquals(
            ['add', 'divide', 'subtract'],
            $eventStream->filterByAscendantCategories(['subtract', 'add', 'op', 'foo', 'divide'])
        );
    }

    public function testFilterAllStreamWithoutInternal(): void
    {
        $eventStream = new InMemoryEventStream();

        $eventStream->createStream('subtract', null);
        $eventStream->createStream('add', null);

        $this->assertEquals(['subtract', 'add'], $eventStream->allWithoutInternal());

        $eventStream->createStream('$all', null);

        $this->assertEquals(['subtract', 'add'], $eventStream->allWithoutInternal());
    }

    public function testDeleteStream(): void
    {
        $eventStream = new InMemoryEventStream();

        $this->assertFalse($eventStream->hasRealStreamName('balance'));
        $this->assertFalse($eventStream->deleteStream('balance'));

        /** @phpstan-ignore-next-line  */
        $created = $eventStream->createStream('balance', '');

        $this->assertTrue($created);
        $this->assertTrue($eventStream->hasRealStreamName('balance'));

        $this->assertTrue($eventStream->deleteStream('balance'));
        $this->assertFalse($eventStream->hasRealStreamName('balance'));
    }
}
