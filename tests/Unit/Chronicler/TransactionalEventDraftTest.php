<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Chronicler\TransactionalEventDraft;
use Chronhub\Storm\Chronicler\Exceptions\TransactionNotStarted;
use Chronhub\Storm\Chronicler\Exceptions\TransactionAlreadyStarted;

final class TransactionalEventDraftTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_assert_null_exceptions(): void
    {
        $draft = new TransactionalEventDraft(null);

        $this->assertFalse($draft->hasException());
        $this->assertFalse($draft->hasTransactionNotStarted());
        $this->assertFalse($draft->hasTransactionAlreadyStarted());
        $this->assertNull($draft->exception());
    }

    /**
     * @test
     */
    public function it_assert_transaction_not_started_exception(): void
    {
        $draft = new TransactionalEventDraft(null);

        $this->assertFalse($draft->hasTransactionNotStarted());

        $exception = new TransactionNotStarted('foo');

        $draft->withRaisedException($exception);

        $this->assertTrue($draft->hasTransactionNotStarted());

        $this->assertSame($exception, $draft->exception());
    }

    /**
     * @test
     */
    public function it_assert_transaction_already_started_exception(): void
    {
        $draft = new TransactionalEventDraft(null);

        $this->assertFalse($draft->hasTransactionAlreadyStarted());

        $exception = new TransactionAlreadyStarted('foo');

        $draft->withRaisedException($exception);

        $this->assertTrue($draft->hasTransactionAlreadyStarted());

        $this->assertSame($exception, $draft->exception());
    }
}
