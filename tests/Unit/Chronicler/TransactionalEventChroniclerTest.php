<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Chronhub\Storm\Chronicler\Exceptions\TransactionNotStarted;
use Chronhub\Storm\Chronicler\TrackTransactionalStream;
use Chronhub\Storm\Chronicler\TransactionalEventChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamStory;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamTracker;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(TransactionalEventChronicler::class)]
final class TransactionalEventChroniclerTest extends UnitTestCase
{
    private TransactionalChronicler|MockObject $chronicler;

    private TransactionalStreamTracker $tracker;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->chronicler = $this->createMock(TransactionalEventableChronicler::class);
        $this->tracker = new TrackTransactionalStream();
    }

    #[Test]
    public function it_dispatch_begin_transaction_event(): void
    {
        $this->chronicler->expects($this->once())->method('beginTransaction');

        $instance = $this->chroniclerInstance();

        $this->tracker->watch(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT, $story->currentEvent());
            });

        $instance->beginTransaction();
    }

    #[Test]
    public function it_dispatch_begin_transaction_event_and_raised_exception_if_transaction_already_started(): void
    {
        $this->expectException(TransactionAlreadyStarted::class);

        $this->chronicler
            ->expects($this->once())
            ->method('beginTransaction')
            ->willThrowException(new TransactionAlreadyStarted('foo'));

        $instance = $this->chroniclerInstance();

        $this->tracker->watch(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT, $story->currentEvent());

                $this->assertTrue($story->hasException());
            });

        $instance->beginTransaction();
    }

    #[Test]
    public function it_dispatch_commit_transaction_event(): void
    {
        $this->chronicler->expects($this->once())->method('commitTransaction');

        $instance = $this->chroniclerInstance();

        $this->tracker->watch(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT, $story->currentEvent());
            });

        $instance->commitTransaction();
    }

    #[Test]
    public function it_dispatch_commit_transaction_event_and_raise_exception_if_exception_not_started(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler
            ->expects($this->once())
            ->method('commitTransaction')
            ->willThrowException(new TransactionNotStarted());

        $instance = $this->chroniclerInstance();

        $this->tracker->watch(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT, $story->currentEvent());

                $this->assertTrue($story->hasException());
            });

        $instance->commitTransaction();
    }

    #[Test]
    public function it_dispatch_rollback_transaction_event(): void
    {
        $this->chronicler->expects($this->once())->method('rollbackTransaction');

        $instance = $this->chroniclerInstance();

        $this->tracker->watch(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT, $story->currentEvent());
            });

        $instance->rollbackTransaction();
    }

    #[Test]
    public function it_dispatch_rollback_transaction_event_and_raise_exception_if_exception_not_started(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler
            ->expects($this->once())
            ->method('rollbackTransaction')
            ->willThrowException(new TransactionNotStarted());

        $instance = $this->chroniclerInstance();

        $this->tracker->watch(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT, $story->currentEvent());

                $this->assertTrue($story->hasException());
            });

        $instance->rollbackTransaction();
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_check_if_in_transaction(bool $inTransaction): void
    {
        $this->chronicler->expects($this->once())->method('inTransaction')->willReturn($inTransaction);

        $this->assertEquals($inTransaction, $this->chroniclerInstance()->inTransaction());
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_handle_full_transaction(bool $bool): void
    {
        $callback = static fn (): bool => $bool;

        $this->chronicler->expects($this->once())->method('transactional')->with($callback)->willReturn($bool);

        $this->assertEquals($bool, $this->chroniclerInstance()->transactional($callback));
    }

    private function chroniclerInstance(): TransactionalEventChronicler
    {
        return new TransactionalEventChronicler($this->chronicler, $this->tracker);
    }

    public static function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}
