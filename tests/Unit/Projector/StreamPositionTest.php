<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

#[CoversClass(StreamPosition::class)]
final class StreamPositionTest extends UnitTestCase
{
    private EventStreamProvider|MockObject $eventStreamProvider;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventStreamProvider = $this->createMock(EventStreamProvider::class);
    }

    #[Test]
    public function it_can_be_constructed(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $this->assertEmpty($streamPosition->all());
    }

    #[Test]
    public function it_watch_streams(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $this->assertEmpty($streamPosition->all());

        $streamPosition->watch(['names' => ['account', 'customer']]);

        $this->assertEquals(['customer' => 0, 'account' => 0], $streamPosition->all());
    }

    #[Test]
    public function it_discover_all_streams(): void
    {
        $this->eventStreamProvider->expects($this->once())
            ->method('allWithoutInternal')
            ->willReturn(['customer', 'account']);

        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['all' => true]);

        $this->assertEquals(['customer' => 0, 'account' => 0], $streamPosition->all());
    }

    #[Test]
    public function it_discover_categories_streams(): void
    {
        $this->eventStreamProvider->expects($this->once())
            ->method('filterByCategories')
            ->with(['account', 'customer'])
            ->willReturn(['customer-123', 'account-123']);

        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['categories' => ['account', 'customer']]);

        $this->assertEquals(['customer-123' => 0, 'account-123' => 0], $streamPosition->all());
    }

    #[Test]
    public function it_discover_streams_names(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['names' => ['account', 'customer']]);

        $this->assertEquals(['customer' => 0, 'account' => 0], $streamPosition->all());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidStreamsNames')]
    #[Test]
    public function it_raise_exception_when_stream_names_is_empty(array $streamNames): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Stream names can not be empty');

        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch($streamNames);
    }

    #[Test]
    public function it_merge_remote_streams(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['names' => ['account', 'customer']]);

        $streamPosition->discover(['account' => 25, 'customer' => 25]);

        $this->assertEquals(['customer' => 25, 'account' => 25], $streamPosition->all());
    }

    #[Test]
    public function it_merge_remote_streams_with_a_new_stream(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['names' => ['account', 'customer']]);

        $streamPosition->discover(['account' => 25, 'customer' => 25, 'passwords' => 10]);

        $this->assertEquals(['customer' => 25, 'account' => 25, 'passwords' => 10], $streamPosition->all());
    }

    #[Test]
    public function it_set_stream_at_position(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['names' => ['account', 'customer']]);

        $streamPosition->discover(['account' => 25, 'customer' => 25]);

        $this->assertEquals(['customer' => 25, 'account' => 25], $streamPosition->all());

        $streamPosition->bind('account', 26);

        $this->assertEquals(['customer' => 25, 'account' => 26], $streamPosition->all());
    }

    #[Test]
    public function it_check_if_next_position_match_current_event_position(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['names' => ['account', 'customer']]);

        $streamPosition->discover(['account' => 25, 'customer' => 20]);

        $this->assertTrue($streamPosition->hasNextPosition('account', 26));
        $this->assertFalse($streamPosition->hasNextPosition('account', 27));
        $this->assertTrue($streamPosition->hasNextPosition('customer', 21));
        $this->assertFalse($streamPosition->hasNextPosition('customer', 22));
    }

    #[Test]
    public function it_reset_stream_positions(): void
    {
        $streamPosition = new StreamPosition($this->eventStreamProvider);

        $streamPosition->watch(['names' => ['account', 'customer']]);

        $this->assertEquals(['customer' => 0, 'account' => 0], $streamPosition->all());

        $streamPosition->reset();

        $this->assertEquals([], $streamPosition->all());
    }

    public static function provideInvalidStreamsNames(): Generator
    {
        yield [[]];
        yield [['names' => []]];
        yield [['names' => null]];
        yield [['invalid_key' => []]];
    }
}
