<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\Scheme\ProjectionState;

#[CoversClass(ProjectionState::class)]
final class ProjectionStateTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $state = new ProjectionState();

        $this->assertSame([], $state->get());
    }

    public function testSetState(): void
    {
        $state = new ProjectionState();

        $state->put(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $state->get());
    }

    public function testOverrideState(): void
    {
        $state = new ProjectionState();

        $state->put(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $state->get());

        $state->put(['baz' => 'foo_bar']);

        $this->assertSame(['baz' => 'foo_bar'], $state->get());
    }

    public function testSetEmptyState(): void
    {
        $state = new ProjectionState();

        $state->put([]);

        $this->assertSame([], $state->get());
    }

    public function testResetState(): void
    {
        $state = new ProjectionState();

        $state->put(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $state->get());

        $state->reset();

        $this->assertSame([], $state->get());
    }
}
