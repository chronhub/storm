<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Exception;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Reporter\Exceptions\MessageCollectedException;

#[CoversClass(MessageCollectedException::class)]
class MessageCollectedExceptionTest extends UnitTestCase
{
    #[Test]
    public function it_collect_exceptions(): void
    {
        $exceptions = [new Exception('foo'), new Exception('bar')];

        $exception = MessageCollectedException::fromExceptions(...$exceptions);

        self::assertCount(2, $exception->getExceptions());
        self::assertEquals($exceptions, $exception->getExceptions());
    }

    #[Test]
    public function it_print_exception_messages(): void
    {
        $exceptions = [new Exception('foo'), new Exception('bar')];

        $exception = MessageCollectedException::fromExceptions(...$exceptions);

        self::assertEquals(
            "One or many event handler(s) cause exception\nfoo\nbar\n",
            $exception->getMessage()
        );
    }
}
