<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Producer;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Producer\QueueOptions;

final class QueueOptionsTest extends UnitTestCase
{
    #[Test]
    public function it_assert_default_properties(): void
    {
        $queueFactory = new QueueOptions();

        $this->assertNull($queueFactory->connection);
        $this->assertNull($queueFactory->name);
        $this->assertNull($queueFactory->delay);
        $this->assertNull($queueFactory->maxExceptions);
        $this->assertNull($queueFactory->timeout);
        $this->assertNull($queueFactory->tries);
        $this->assertNull($queueFactory->backoff);
    }

    #[Test]
    public function it_can_override_some_arguments_by_passing_associative_array(): void
    {
        $queueFactory = new QueueOptions(...[
            'connection' => 'redis',
            'name' => 'default',
            'delay' => 5,
            'maxExceptions' => 3,
            'timeout' => 30,
            'tries' => 1,
            'backoff' => 5,
        ]);

        $this->assertEquals('redis', $queueFactory->connection);
        $this->assertEquals('default', $queueFactory->name);
        $this->assertEquals(5, $queueFactory->delay);
        $this->assertEquals(3, $queueFactory->maxExceptions);
        $this->assertEquals(30, $queueFactory->timeout);
        $this->assertEquals(1, $queueFactory->tries);
        $this->assertEquals(5, $queueFactory->backoff);
    }

    #[Test]
    public function it_can_override_some_arguments_with_promoted_arguments(): void
    {
        $queueFactory = new QueueOptions(name: 'default', tries: 2, maxExceptions: 4);

        $this->assertEquals('default', $queueFactory->name);
        $this->assertEquals(2, $queueFactory->tries);
        $this->assertEquals(4, $queueFactory->maxExceptions);

        $this->assertNull($queueFactory->connection);
        $this->assertNull($queueFactory->delay);
        $this->assertNull($queueFactory->timeout);
        $this->assertNull($queueFactory->backoff);
    }

    #[Test]
    public function it_return_associative_array(): void
    {
        $queueFactory = new QueueOptions(...[
            'connection' => 'redis',
            'name' => 'default',
            'delay' => 5,
            'maxExceptions' => 3,
            'timeout' => 30,
            'tries' => 1,
            'backoff' => 10,
        ]);

        $this->assertEquals([
            'connection' => 'redis',
            'name' => 'default',
            'delay' => 5,
            'timeout' => 30,
            'tries' => 1,
            'max_exceptions' => 3,
            'backoff' => 10,
        ], $queueFactory->jsonSerialize());
    }
}
