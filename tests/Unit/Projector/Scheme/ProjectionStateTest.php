<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\Scheme\ProjectionState;

#[CoversClass(ProjectionState::class)]
final class ProjectionStateTest extends UnitTestCase
{
    #[Test]
    public function it_can_be_constructed_with_empty_state(): void
    {
        $state = new ProjectionState();

        $this->assertEmpty($state->get());
    }

    #[Test]
    public function it_put_state(): void
    {
        $state = new ProjectionState();

        $state->put(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $state->get());
    }

    #[Test]
    public function it_override_state(): void
    {
        $state = new ProjectionState();

        $state->put(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $state->get());

        $state->put(['bar' => 'foo']);

        $this->assertEquals(['bar' => 'foo'], $state->get());
    }

    #[Test]
    public function it_reset_state(): void
    {
        $state = new ProjectionState();

        $state->put(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $state->get());

        $state->reset();

        $this->assertEquals([], $state->get());
    }
}
