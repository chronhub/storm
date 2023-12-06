<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Producer;

use Chronhub\Storm\Producer\QueueOptions;
use Chronhub\Storm\Tests\UnitTestCase;

final class QueueOptionsTest extends UnitTestCase
{
    public function testInstance(): void
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

    public function testOverrideQueueOptionsWithArray(): void
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

    public function testOverrideQueueOptionsWithPromotion(): void
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

    public function testJsonSerialize(): void
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
