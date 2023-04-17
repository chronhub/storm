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
    private InMemoryEventStream $eventStream;

    protected function setUp(): void
    {
        $this->eventStream = new InMemoryEventStream();
    }

    public function testCreateStream(): void
    {
        $this->assertFalse($this->eventStream->hasRealStreamName('balance'));

        $created = $this->eventStream->createStream('balance', null);

        $this->assertTrue($created);
        $this->assertTrue($this->eventStream->hasRealStreamName('balance'));
    }

    public function testReturnFalseWhenStreamAlreadyExists(): void
    {
        $this->assertFalse($this->eventStream->hasRealStreamName('balance'));

        $created = $this->eventStream->createStream('balance', null);

        $this->assertTrue($created);

        $this->assertFalse($this->eventStream->createStream('balance', null));
        $this->assertFalse($this->eventStream->createStream('balance', null, 'operation'));
    }

    public function testCreateCategory(): void
    {
        $created = $this->eventStream->createStream('add', null, 'operation');

        $this->assertTrue($created);
        $this->assertTrue($this->eventStream->hasRealStreamName('add'));

        $this->assertEquals(['add'], $this->eventStream->filterByAscendantCategories(['operation']));
    }

    public function testFilterByAscendantStreamNamesWithStreamNameInstanceOrString(): void
    {
        $this->eventStream->createStream('subtract', null);
        $this->eventStream->createStream('add', null);

        $this->assertEquals(
            ['add', 'subtract'],
            $this->eventStream->filterByAscendantStreams([new StreamName('subtract'), 'add'])
        );

        $this->assertEquals(
            ['add', 'subtract'],
            $this->eventStream->filterByAscendantStreams(['subtract', new StreamName('add'), ' balance', 'foo'])
        );
    }

    public function testFilterCategoriesByAscendantStreamNames(): void
    {
        $this->eventStream->createStream('subtract', null, 'op');
        $this->eventStream->createStream('add', null, 'op');
        $this->eventStream->createStream('divide', null, 'op');

        $this->assertEquals(
            ['add', 'divide', 'subtract'],
            $this->eventStream->filterByAscendantCategories(['subtract', 'add', 'op', 'foo', 'divide'])
        );
    }

    public function testFilterAllStreamWithoutInternal(): void
    {
        $this->eventStream->createStream('subtract', null);
        $this->eventStream->createStream('add', null);

        $this->assertEquals(['subtract', 'add'], $this->eventStream->allWithoutInternal());

        $this->eventStream->createStream('$all', null);

        $this->assertEquals(['subtract', 'add'], $this->eventStream->allWithoutInternal());
    }

    public function testDeleteStream(): void
    {
        $this->assertFalse($this->eventStream->hasRealStreamName('balance'));
        $this->assertFalse($this->eventStream->deleteStream('balance'));

        $created = $this->eventStream->createStream('balance', '');

        $this->assertTrue($created);
        $this->assertTrue($this->eventStream->hasRealStreamName('balance'));

        $this->assertTrue($this->eventStream->deleteStream('balance'));
        $this->assertFalse($this->eventStream->hasRealStreamName('balance'));
    }
}
