<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Chronicler\Exceptions\RuntimeException;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(StreamAlreadyExists::class)]
class StreamAlreadyExistsTest extends UnitTestCase
{
    public function testException(): void
    {
        $exception = StreamAlreadyExists::withStreamName(new StreamName('foo'));

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertEquals('Stream foo already exists', $exception->getMessage());
    }
}
