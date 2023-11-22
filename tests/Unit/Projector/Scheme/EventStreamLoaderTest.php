<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Scheme\EventStreamLoader;
use Chronhub\Storm\Tests\UnitTestCase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\MockObject\MockObject;

class EventStreamLoaderTest extends UnitTestCase
{
    private EventStreamProvider|MockObject $eventStreamProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventStreamProvider = $this->createMock(EventStreamProvider::class);
    }

    public function testLoadFromAll(): void
    {
        $this->eventStreamProvider->expects($this->once())
            ->method('allWithoutInternal')
            ->willReturn(['stream1', 'stream2']);

        $result = $this->newEventStreamLoader()->loadFrom(['all' => true]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(['stream1', 'stream2'], $result->toArray());
    }

    public function testLoadFromCategories(): void
    {
        $this->eventStreamProvider->expects($this->once())
            ->method('filterByAscendantCategories')
            ->with(['category1', 'category2'])
            ->willReturn(['stream3', 'stream4']);

        $result = $this->newEventStreamLoader()->loadFrom(['categories' => ['category1', 'category2']]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(['stream3', 'stream4'], $result->toArray());
    }

    public function testLoadFromNames(): void
    {
        $this->eventStreamProvider->expects($this->never())
            ->method('allWithoutInternal');

        $loader = new EventStreamLoader($this->eventStreamProvider);

        $result = $loader->loadFrom(['names' => ['stream5', 'stream6']]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(['stream5', 'stream6'], $result->toArray());
    }

    public function testLoadFromEmptyStreamNames(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No stream set or found');

        $this->newEventStreamLoader()->loadFrom(['names' => []]);
    }

    public function testLoadFromDuplicateStreamNames(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate stream names is not allowed');

        $this->newEventStreamLoader()->loadFrom(['names' => ['duplicate', 'duplicate']]);
    }

    public function testLoadFromDuplicateCategories(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate stream names is not allowed');

        $this->eventStreamProvider->expects($this->once())
            ->method('filterByAscendantCategories')
            ->with(['category1', 'category2'])
            ->willReturn(['duplicate', 'duplicate']);

        $this->newEventStreamLoader()->loadFrom(['categories' => ['category1', 'category2']]);
    }

    public function testLoadFromDuplicateAll(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate stream names is not allowed');

        $this->eventStreamProvider->expects($this->once())
            ->method('allWithoutInternal')
            ->willReturn(['duplicate', 'duplicate']);

        $this->newEventStreamLoader()->loadFrom(['all' => true]);
    }

    private function newEventStreamLoader(): EventStreamLoader
    {
        return new EventStreamLoader($this->eventStreamProvider);
    }
}
