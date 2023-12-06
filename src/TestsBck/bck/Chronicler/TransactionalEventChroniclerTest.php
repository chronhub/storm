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
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(TransactionalEventChronicler::class)]
final class TransactionalEventChroniclerTest extends UnitTestCase
{
    private TransactionalChronicler|MockObject $chronicler;

    private TransactionalStreamTracker $tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chronicler = $this->createMock(TransactionalEventableChronicler::class);
        $this->tracker = new TrackTransactionalStream();
    }

    public function testDispatchBeginTransactionEvent(): void
    {
        $this->chronicler->expects($this->once())->method('beginTransaction');

        $instance = $this->newChronicler();

        $this->tracker->watch(
            TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT, $story->currentEvent());
            });

        $instance->beginTransaction();
    }

    public function testTransactionAlreadyStartedRaisedWhenDispatchBeginTransactionEvent(): void
    {
        $this->expectException(TransactionAlreadyStarted::class);

        $this->chronicler
            ->expects($this->once())
            ->method('beginTransaction')
            ->willThrowException(new TransactionAlreadyStarted('foo'));

        $instance = $this->newChronicler();

        $this->tracker->watch(
            TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT, $story->currentEvent());

                $this->assertTrue($story->hasException());
            });

        $instance->beginTransaction();
    }

    public function testDispatchCommitTransactionEvent(): void
    {
        $this->chronicler->expects($this->once())->method('commitTransaction');

        $instance = $this->newChronicler();

        $this->tracker->watch(
            TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT, $story->currentEvent());
            });

        $instance->commitTransaction();
    }

    public function testTransactionNotStartedRaisedWhenDispatchCommitTransactionEvent(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler
            ->expects($this->once())
            ->method('commitTransaction')
            ->willThrowException(new TransactionNotStarted());

        $instance = $this->newChronicler();

        $this->tracker->watch(
            TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT, $story->currentEvent());

                $this->assertTrue($story->hasException());
            });

        $instance->commitTransaction();
    }

    public function testDispatchRollbackTransactionEvent(): void
    {
        $this->chronicler->expects($this->once())->method('rollbackTransaction');

        $instance = $this->newChronicler();

        $this->tracker->watch(
            TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT, $story->currentEvent());
            });

        $instance->rollbackTransaction();
    }

    public function testTransactionNotStartedRaisedWhenDispatchRollbackTransactionEvent(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler
            ->expects($this->once())
            ->method('rollbackTransaction')
            ->willThrowException(new TransactionNotStarted());

        $instance = $this->newChronicler();

        $this->tracker->watch(
            TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT, $story->currentEvent());

                $this->assertTrue($story->hasException());
            });

        $instance->rollbackTransaction();
    }

    #[DataProvider('provideBoolean')]
    public function testCheckInTransaction(bool $inTransaction): void
    {
        $this->chronicler->expects($this->once())->method('inTransaction')->willReturn($inTransaction);

        $this->assertEquals($inTransaction, $this->newChronicler()->inTransaction());
    }

    #[DataProvider('provideBoolean')]
    public function testFullyTransactional(bool $bool): void
    {
        $callback = static fn (): bool => $bool;

        $this->chronicler->expects($this->once())->method('transactional')->with($callback)->willReturn($bool);

        $this->assertEquals($bool, $this->newChronicler()->transactional($callback));
    }

    private function newChronicler(): TransactionalEventChronicler
    {
        return new TransactionalEventChronicler($this->chronicler, $this->tracker);
    }

    public static function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}
