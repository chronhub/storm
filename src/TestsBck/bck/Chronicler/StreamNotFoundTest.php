<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Chronicler\Exceptions\RuntimeException;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;

class StreamNotFoundTest extends UnitTestCase
{
    public function testException(): void
    {
        $exception = StreamNotFound::withStreamName(new StreamName('foo'));

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertEquals('Stream foo not found', $exception->getMessage());
    }
}
