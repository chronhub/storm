<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Chronhub\Storm\Reporter\Exceptions\MessageCollectedException;
use Chronhub\Storm\Tests\UnitTestCase;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MessageCollectedException::class)]
class MessageCollectedExceptionTest extends UnitTestCase
{
    public function testCollectExceptions(): void
    {
        $exceptions = [new Exception('foo'), new Exception('bar')];

        $exception = MessageCollectedException::fromExceptions(...$exceptions);

        self::assertCount(2, $exception->getExceptions());
        self::assertEquals($exceptions, $exception->getExceptions());
    }

    public function testConcatExceptionMessagesCollected(): void
    {
        $exceptions = [new Exception('foo'), new Exception('bar')];

        $exception = MessageCollectedException::fromExceptions(...$exceptions);

        self::assertEquals(
            "One or many event handler(s) cause exception\nfoo\nbar\n",
            $exception->getMessage()
        );
    }
}
