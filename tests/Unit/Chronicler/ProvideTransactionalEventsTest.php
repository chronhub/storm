<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Chronhub\Storm\Chronicler\ProvideEvents;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Chronicler\TrackTransactionalStream;
use Chronhub\Storm\Chronicler\TransactionalEventChronicler;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamStory;
use Chronhub\Storm\Chronicler\Exceptions\TransactionNotStarted;
use Chronhub\Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;

#[CoversClass(ProvideEvents::class)]
class ProvideTransactionalEventsTest extends UnitTestCase
{
    private TransactionalEventableChronicler|MockObject $chronicler;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->chronicler = $this->createMock(TransactionalEventableChronicler::class);
    }

    #[Test]
    public function it_start_transaction(): void
    {
        $this->chronicler->expects($this->once())->method('beginTransaction');

        $this->transactionalInstance(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT, function (TransactionalStreamStory $story): void {
            $this->assertEquals(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT, $story->currentEvent());
        })->beginTransaction();
    }

    #[Test]
    public function it_raise_exception_when_transaction_already_started(): void
    {
        $this->expectException(TransactionAlreadyStarted::class);

        $this->chronicler->expects($this->once())->method('beginTransaction')->willThrowException(new TransactionAlreadyStarted());

        $this->transactionalInstance(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT, function (TransactionalStreamStory $story): void {
            $this->assertEquals(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT, $story->currentEvent());
            $this->assertTrue($story->hasTransactionAlreadyStarted());
        })->beginTransaction();
    }

    #[Test]
    public function it_commit_transaction(): void
    {
        $this->chronicler->expects($this->once())->method('commitTransaction');

        $this->transactionalInstance(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT, function (TransactionalStreamStory $story): void {
            $this->assertEquals(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT, $story->currentEvent());
        })->commitTransaction();
    }

    #[Test]
    public function it_raise_transaction_not_started_on_commit_transaction(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler->expects($this->once())->method('commitTransaction')->willThrowException(new TransactionNotStarted());

        $this->transactionalInstance(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT, function (TransactionalStreamStory $story): void {
            $this->assertEquals(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT, $story->currentEvent());
            $this->assertTrue($story->hasTransactionNotStarted());
        })->commitTransaction();
    }

    #[Test]
    public function it_rollback_transaction(): void
    {
        $this->chronicler->expects($this->once())->method('rollbackTransaction');

        $this->transactionalInstance(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT, function (TransactionalStreamStory $story): void {
            $this->assertEquals(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT, $story->currentEvent());
        })->rollbackTransaction();
    }

    #[Test]
    public function it_raise_transaction_not_started_on_rollback_transaction(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler->expects($this->once())->method('rollbackTransaction')->willThrowException(new TransactionNotStarted());

        $this->transactionalInstance(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT, function (TransactionalStreamStory $story): void {
            $this->assertEquals(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT, $story->currentEvent());
            $this->assertTrue($story->hasTransactionNotStarted());
        })->rollbackTransaction();
    }

    private function transactionalInstance(string $event, callable $story): TransactionalEventableChronicler
    {
        $eventStore = new TransactionalEventChronicler($this->chronicler, new TrackTransactionalStream());

        $eventStore->subscribe($event, $story);

        return $eventStore;
    }
}
