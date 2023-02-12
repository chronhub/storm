<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Generator;
use TypeError;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Chronicler\TrackTransactionalStream;
use Chronhub\Storm\Chronicler\TransactionalEventChronicler;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamStory;
use Chronhub\Storm\Chronicler\Exceptions\TransactionNotStarted;
use Chronhub\Storm\Contracts\Chronicler\TransactionalChronicler;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamTracker;
use Chronhub\Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;

final class TransactionalEventChroniclerTest extends ProphecyTestCase
{
    private TransactionalChronicler|ObjectProphecy $chronicler;

    private TransactionalStreamTracker $tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chronicler = $this->prophesize(TransactionalEventableChronicler::class);
        $this->tracker = new TrackTransactionalStream();
    }

    /**
     * @test
     */
    public function it_dispatch_begin_transaction_event(): void
    {
        $this->chronicler->beginTransaction()->shouldBeCalled();

        $instance = $this->chroniclerInstance();

        $this->tracker->watch(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT, $story->currentEvent());
            });

        $instance->beginTransaction();
    }

    /**
     * @test
     */
    public function it_dispatch_begin_transaction_event_and_raised_exception_if_transaction_already_started(): void
    {
        $this->expectException(TransactionAlreadyStarted::class);

        $this->chronicler
            ->beginTransaction()
            ->willThrow(new TransactionAlreadyStarted('foo'))
            ->shouldBeCalled();

        $instance = $this->chroniclerInstance();

        $this->tracker->watch(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::BEGIN_TRANSACTION_EVENT, $story->currentEvent());

                $this->assertTrue($story->hasException());
            });

        $instance->beginTransaction();
    }

    /**
     * @test
     */
    public function it_dispatch_commit_transaction_event(): void
    {
        $this->chronicler->commitTransaction()->shouldBeCalled();

        $instance = $this->chroniclerInstance();

        $this->tracker->watch(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT, $story->currentEvent());
            });

        $instance->commitTransaction();
    }

    /**
     * @test
     */
    public function it_dispatch_commit_transaction_event_and_raise_exception_if_exception_not_started(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler
            ->commitTransaction()
            ->willThrow(new TransactionNotStarted())
            ->shouldBeCalled();

        $instance = $this->chroniclerInstance();

        $this->tracker->watch(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::COMMIT_TRANSACTION_EVENT, $story->currentEvent());

                $this->assertTrue($story->hasException());
            });

        $instance->commitTransaction();
    }

    /**
     * @test
     */
    public function it_dispatch_rollback_transaction_event(): void
    {
        $this->chronicler->rollbackTransaction()->shouldBeCalled();

        $instance = $this->chroniclerInstance();

        $this->tracker->watch(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT, $story->currentEvent());
            });

        $instance->rollbackTransaction();
    }

    /**
     * @test
     */
    public function it_dispatch_rollback_transaction_event_and_raise_exception_if_exception_not_started(): void
    {
        $this->expectException(TransactionNotStarted::class);

        $this->chronicler
            ->rollbackTransaction()
            ->willThrow(new TransactionNotStarted())
            ->shouldBeCalled();

        $instance = $this->chroniclerInstance();

        $this->tracker->watch(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT,
            function (TransactionalStreamStory $story): void {
                $this->assertEquals(TransactionalEventableChronicler::ROLLBACK_TRANSACTION_EVENT, $story->currentEvent());

                $this->assertTrue($story->hasException());
            });

        $instance->rollbackTransaction();
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     *
     * @param  bool  $inTransaction
     */
    public function it_check_if_in_transaction(bool $inTransaction): void
    {
        $this->chronicler
            ->inTransaction()
            ->willReturn($inTransaction)
            ->shouldBeCalled();

        $this->assertEquals($inTransaction, $this->chroniclerInstance()->inTransaction());
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_handle_full_transaction(bool $bool): void
    {
        $callback = static fn (): bool => $bool;

        $this->chronicler
            ->transactional($callback)
            ->willReturn($callback())
            ->shouldBeCalled();

        $this->assertEquals($bool, $this->chroniclerInstance()->transactional($callback));
    }

    /**
     * @test
     */
    public function it_raise_exception_if_inner_chronicler_is_not_transactional(): void
    {
        $this->expectException(TypeError::class);

        $chronicler = $this->prophesize(Chronicler::class);

        /** @phpstan-ignore-next-line  */
        $transactionalEventChronicler = new TransactionalEventChronicler($chronicler->reveal(), $this->tracker);

        $transactionalEventChronicler->transactional(fn (): string => 'nope');
    }

    private function chroniclerInstance(): TransactionalEventChronicler
    {
        return new TransactionalEventChronicler($this->chronicler->reveal(), $this->tracker);
    }

    public function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}
