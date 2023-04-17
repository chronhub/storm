<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Projector\InMemoryProjection;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InMemoryProjection::class)]
class InMemoryProjectionTest extends UnitTestCase
{
    public function testCreateProjection(): void
    {
        $projection = InMemoryProjection::create('projection', 'running');
        $this->assertInstanceOf(ProjectionModel::class, $projection);

        $this->assertSame('projection', $projection->name());
        $this->assertSame('running', $projection->status());
        $this->assertSame('{}', $projection->position());
        $this->assertSame('{}', $projection->state());
        $this->assertNull($projection->lockedUntil());
    }

    public function testUpdateState(): void
    {
        $projection = InMemoryProjection::create('projection', 'running');

        $this->assertSame('{}', $projection->state());
        $projection->setState('{"count":1}');
        $this->assertSame('{"count":1}', $projection->state());
    }

    public function testUpdatePosition(): void
    {
        $projection = InMemoryProjection::create('projection', 'running');

        $this->assertSame('{}', $projection->position());
        $projection->setPosition('{foo:1}');
        $this->assertSame('{foo:1}', $projection->position());
    }

    public function testUpdateStatus(): void
    {
        $projection = InMemoryProjection::create('projection', 'running');

        $this->assertSame('running', $projection->status());
        $projection->setStatus('idle');
        $this->assertSame('idle', $projection->status());
    }

    public function testUpdateLockedUntil(): void
    {
        $projection = InMemoryProjection::create('projection', 'running');

        $this->assertSame('running', $projection->status());
        $projection->setLockedUntil('datetime');
        $this->assertSame('datetime', $projection->lockedUntil());
    }

    public function testUpdateNullLockedUntil(): void
    {
        $projection = InMemoryProjection::create('projection', 'running');

        $this->assertSame('running', $projection->status());

        $projection->setLockedUntil('datetime');

        $this->assertSame('datetime', $projection->lockedUntil());

        $projection->setLockedUntil(null);

        $this->assertNull($projection->lockedUntil());
    }
}
