<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Projector\Scheme\ProjectionState;

final class ProjectionStateTest extends UnitTestCase
{
    public function it_can_be_constructed_with_empty_state(): void
    {
        $state = new ProjectionState();

        $this->assertEmpty($state->get());
    }

    /**
     * @test
     */
    public function it_put_state(): void
    {
        $state = new ProjectionState();

        $state->put(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $state->get());
    }

    /**
     * @test
     */
    public function it_override_state(): void
    {
        $state = new ProjectionState();

        $state->put(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $state->get());

        $state->put(['bar' => 'foo']);

        $this->assertEquals(['bar' => 'foo'], $state->get());
    }

    /**
     * @test
     */
    public function it_reset_state(): void
    {
        $state = new ProjectionState();

        $state->put(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $state->get());

        $state->reset();

        $this->assertEquals([], $state->get());
    }

    /**
     * @test
     */
    public function it_serialize_state(): void
    {
        $state = new ProjectionState();

        $state->put(['foo' => 'bar']);

        $this->assertEquals('{"foo":"bar"}', $state->jsonSerialize());
    }

    /**
     * @test
     */
    public function it_serialize_to_json_object_from_empty_state(): void
    {
        $state = new ProjectionState();

        $this->assertEquals([], $state->get());

        $this->assertEquals('{}', $state->jsonSerialize());
    }
}
