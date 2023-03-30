<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Stream;

use InvalidArgumentException;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(StreamName::class)]
final class StreamNameTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $streamName = new StreamName('some_stream_name');

        $this->assertEquals($streamName, $streamName->name);
    }

    public function testInstanceCanBeSerialized(): void
    {
        $streamName = new StreamName('some_stream_name');

        $this->assertEquals($streamName, $streamName->__toString());
        $this->assertEquals($streamName, $streamName->toString());
        $this->assertEquals($streamName, (string) $streamName);
    }

    public function testRaiseExceptionWithEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stream name given can not be empty');

        new StreamName('');
    }
}
