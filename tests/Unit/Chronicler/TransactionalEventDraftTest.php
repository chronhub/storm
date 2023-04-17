<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Chronicler\Exceptions\TransactionAlreadyStarted;
use Chronhub\Storm\Chronicler\Exceptions\TransactionNotStarted;
use Chronhub\Storm\Chronicler\TransactionalEventDraft;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TransactionalEventDraft::class)]
final class TransactionalEventDraftTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $draft = new TransactionalEventDraft(null);

        $this->assertFalse($draft->hasException());
        $this->assertFalse($draft->hasTransactionNotStarted());
        $this->assertFalse($draft->hasTransactionAlreadyStarted());
        $this->assertNull($draft->exception());
    }

    public function testHasTransactionNotStarted(): void
    {
        $draft = new TransactionalEventDraft(null);

        $this->assertFalse($draft->hasTransactionNotStarted());

        $exception = new TransactionNotStarted('foo');

        $draft->withRaisedException($exception);

        $this->assertTrue($draft->hasTransactionNotStarted());

        $this->assertSame($exception, $draft->exception());
    }

    public function testHasTransactionAlreadyStarted(): void
    {
        $draft = new TransactionalEventDraft(null);

        $this->assertFalse($draft->hasTransactionAlreadyStarted());

        $exception = new TransactionAlreadyStarted('foo');

        $draft->withRaisedException($exception);

        $this->assertTrue($draft->hasTransactionAlreadyStarted());

        $this->assertSame($exception, $draft->exception());
    }
}
