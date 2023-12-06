<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Chronhub\Storm\Chronicler\Exceptions\TransactionNotStarted;
use Chronhub\Storm\Chronicler\ProvideEvents;
use Chronhub\Storm\Chronicler\TrackTransactionalStream;
use Chronhub\Storm\Chronicler\TransactionalEventChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamStory;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(ProvideEvents::class)]
class ProvideTransactionalEventsTest extends UnitTestCase
{
    private TransactionalEventableChronicler|MockObject $chronicler;

    protected function setUp(): void
    {
        $this->chronicler = $this->createMock(TransactionalEventableChronicler::class);
    }

    public function testDispatchBeginTransactionEvent(): void
    {
        $this->chronicler->expects($this->once())->method('beginTransaction');

        $this->transactionalInstance(
            TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT, $story->currentEvent());
            }
        )->beginTransaction();
    }

    public function testExceptionRaisedWhenDispatchBeginTransactionEvent(): void
    {
        $this->expectException(TransactionAlreadyStarted::class);

        $this->chronicler
            ->expects($this->once())
            ->method('beginTransaction')
            ->willThrowException(new TransactionAlreadyStarted());

        $this->transactionalInstance(
            TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT, $story->currentEvent());
                $this->assertTrue($story->hasTransactionAlreadyStarted());
            }
        )->beginTransaction();
    }

    public function testDispatchCommitTransactionEvent(): void
    {
        $this->chronicler->expects($this->once())->method('commitTransaction');

        $this->transactionalInstance(
            TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT, $story->currentEvent());
            }
        )->commitTransaction();
    }

    public function testTransactionNotStartedRaisedWhenDispatchCommitTransactionEvent(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler->expects($this->once())->method('commitTransaction')->willThrowException(new TransactionNotStarted());

        $this->transactionalInstance(
            TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT, $story->currentEvent());
                $this->assertTrue($story->hasTransactionNotStarted());
            }
        )->commitTransaction();
    }

    public function testDispatchRollbackTransactionEvent(): void
    {
        $this->chronicler->expects($this->once())->method('rollbackTransaction');

        $this->transactionalInstance(
            TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT, $story->currentEvent());
            }
        )->rollbackTransaction();
    }

    public function testTransactionNotStartedRaisedWhenDispatchRollbackTransactionEvent(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler
            ->expects($this->once())
            ->method('rollbackTransaction')
            ->willThrowException(new TransactionNotStarted());

        $this->transactionalInstance(
            TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT, $story->currentEvent());
                $this->assertTrue($story->hasTransactionNotStarted());
            }
        )->rollbackTransaction();
    }

    private function transactionalInstance(string $event, callable $story): TransactionalEventableChronicler
    {
        $eventStore = new TransactionalEventChronicler($this->chronicler, new TrackTransactionalStream());

        $eventStore->subscribe($event, $story);

        return $eventStore;
    }
}
