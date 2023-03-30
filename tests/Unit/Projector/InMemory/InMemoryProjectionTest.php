<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\InMemory;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\Provider\InMemoryProjection;

#[CoversClass(InMemoryProjection::class)]
final class InMemoryProjectionTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $projection = InMemoryProjection::create('customer', 'running');

        $this->assertEquals('customer', $projection->name());

        $this->assertEquals('running', $projection->status());

        $this->assertEquals('{}', $projection->state());

        $this->assertEquals('{}', $projection->position());

        $this->assertNull($projection->lockedUntil());
    }

    public function TestStateSetter(): void
    {
        $projection = InMemoryProjection::create('customer', 'running');

        $projection->setState('{}');

        $this->assertEquals('{}', $projection->state());

        $projection->setState('{"count": 10}');

        $this->assertEquals('{"count": 10}', $projection->state());
    }

    public function testPositionSetter(): void
    {
        $projection = InMemoryProjection::create('customer', 'running');

        $projection->setPosition('{}');

        $this->assertEquals('{}', $projection->position());

        $projection->setPosition('{"account": 10}');

        $this->assertEquals('{"account": 10}', $projection->position());
    }

    public function testStatusSetter(): void
    {
        $projection = InMemoryProjection::create('customer', 'running');

        $this->assertEquals('running', $projection->status());

        $projection->setStatus('idle');

        $this->assertEquals('idle', $projection->status());
    }

    public function testLockedUntilSetter(): void
    {
        $projection = InMemoryProjection::create('customer', 'running');

        $this->assertNull($projection->lockedUntil());

        $projection->setLockedUntil('datetime');
        $this->assertEquals('datetime', $projection->lockedUntil());

        $projection->setLockedUntil(null);

        $this->assertEquals(null, $projection->lockedUntil());
    }
}
