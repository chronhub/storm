<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Stream;

use InvalidArgumentException;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;

final class StreamNameTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_instantiate_with_name(): void
    {
        $streamName = new StreamName('some_stream_name');

        $this->assertEquals($streamName, $streamName->name);
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $streamName = new StreamName('some_stream_name');

        $this->assertEquals($streamName, $streamName->__toString());
        $this->assertEquals($streamName, $streamName->toString());
        $this->assertEquals($streamName, (string) $streamName);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_name_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stream name given can not be empty');

        new StreamName('');
    }
}
