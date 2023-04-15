<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(StreamPosition::class)]
final class StreamPositionTest extends UnitTestCase
{
    private EventStreamProvider|MockObject $eventStreamProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventStreamProvider = $this->createMock(EventStreamProvider::class);
    }

    public function testInstance(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $this->assertEmpty($streamPosition->all());
    }

    public function testWatchStreams(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $this->assertEmpty($streamPosition->all());

        $streamPosition->watch(['names' => ['account', 'customer']]);

        $this->assertEquals(['customer' => 0, 'account' => 0], $streamPosition->all());
    }

    public function testDiscoverAllStreams(): void
    {
        $this->eventStreamProvider->expects($this->once())
            ->method('allWithoutInternal')
            ->willReturn(['customer', 'account']);

        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['all' => true]);

        $this->assertEquals(['customer' => 0, 'account' => 0], $streamPosition->all());
    }

    public function testDiscoverCategoryStreams(): void
    {
        $this->eventStreamProvider->expects($this->once())
            ->method('filterByAscendantCategories')
            ->with(['account', 'customer'])
            ->willReturn(['customer-123', 'account-123']);

        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['categories' => ['account', 'customer']]);

        $this->assertEquals(['customer-123' => 0, 'account-123' => 0], $streamPosition->all());
    }

    public function testDiscoverStreamNames(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['names' => ['account', 'customer']]);

        $this->assertEquals(['customer' => 0, 'account' => 0], $streamPosition->all());
    }

    #[DataProvider('provideInvalidStreamsNames')]
    public function testExceptionRaisedWhenStreamNamesIsEmpty(array $streamNames): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Stream names can not be empty');

        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch($streamNames);
    }

    public function testMergeDiscoverStreamNameWithCurrentStreamPositions(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['names' => ['account', 'customer']]);

        $this->assertEquals(['customer' => 0, 'account' => 0], $streamPosition->all());

        $streamPosition->discover(['account' => 25, 'customer' => 25]);

        $this->assertEquals(['customer' => 25, 'account' => 25], $streamPosition->all());
    }

    public function testMergeDiscoverNewStreamWithCurrentStreamPositions(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['names' => ['account', 'customer']]);

        $streamPosition->discover(['account' => 25, 'customer' => 25, 'passwords' => 10]);

        $this->assertEquals(['customer' => 25, 'account' => 25, 'passwords' => 10], $streamPosition->all());
    }

    public function testBindStreamPosition(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['names' => ['account', 'customer']]);

        $streamPosition->discover(['account' => 25, 'customer' => 25]);

        $this->assertEquals(['customer' => 25, 'account' => 25], $streamPosition->all());

        $streamPosition->bind('account', 26);

        $this->assertEquals(['customer' => 25, 'account' => 26], $streamPosition->all());
    }

    public function testHasNextPosition(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['names' => ['account', 'customer']]);

        $streamPosition->discover(['account' => 25, 'customer' => 20]);

        $this->assertTrue($streamPosition->hasNextPosition('account', 26));
        $this->assertFalse($streamPosition->hasNextPosition('account', 27));
        $this->assertTrue($streamPosition->hasNextPosition('customer', 21));
        $this->assertFalse($streamPosition->hasNextPosition('customer', 22));
    }

    public function testResetStreamPositions(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['names' => ['account', 'customer']]);

        $this->assertEquals(['customer' => 0, 'account' => 0], $streamPosition->all());

        $streamPosition->reset();

        $this->assertEquals([], $streamPosition->all());
    }

    public function testItCanBeJsonSerializedToArray(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['names' => ['account', 'customer']]);

        $this->assertEquals(['customer' => 0, 'account' => 0], $streamPosition->jsonSerialize());
    }

    public static function provideInvalidStreamsNames(): Generator
    {
        yield [[]];
        yield [['names' => []]];
        yield [['names' => null]];
        yield [['invalid_key' => []]];
    }
}
