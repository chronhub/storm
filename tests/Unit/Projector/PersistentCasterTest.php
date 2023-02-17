<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Projector\Scheme\PersistentCaster;
use Chronhub\Storm\Contracts\Projector\ProjectionProjector;

final class PersistentCasterTest extends ProphecyTestCase
{
    private ObjectProphecy|ProjectionProjector $projector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projector = $this->prophesize(ProjectionProjector::class);
    }

    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $streamName = null;

        $caster = new PersistentCaster($this->projector->reveal(), $streamName);

        $this->assertNull($caster->streamName());
    }

    /**
     * @test
     */
    public function it_return_stream_name_by_reference(): void
    {
        $streamName = null;

        $caster = new PersistentCaster($this->projector->reveal(), $streamName);

        $keep = true;
        while ($keep) {
            $this->assertNull($caster->streamName());

            $streamName = 'foo';
            $this->assertEquals('foo', $caster->streamName());

            $streamName = 'bar';
            $this->assertEquals('bar', $caster->streamName());

            $keep = false;
        }
    }

    /**
     * @test
     */
    public function it_can_stop_projection(): void
    {
        $streamName = 'foo';

        $this->projector->stop()->shouldBeCalledOnce();

        $caster = new PersistentCaster($this->projector->reveal(), $streamName);

        $this->assertEquals('foo', $caster->streamName());

        $caster->stop();
    }

    /**
     * @test
     */
    public function it_link_event_to_new_stream(): void
    {
        $streamName = 'foo';
        $event = SomeEvent::fromContent(['name' => 'steph bug']);

        $this->projector->linkTo('foo', $event)->shouldBeCalledOnce();

        $caster = new PersistentCaster($this->projector->reveal(), $streamName);

        $this->assertEquals('foo', $caster->streamName());

        $caster->linkTo('foo', $event);
    }

    /**
     * @test
     */
    public function it_emit_event(): void
    {
        $streamName = 'foo';
        $event = SomeEvent::fromContent(['name' => 'steph bug']);

        $this->projector->emit($event)->shouldBeCalledOnce();

        $caster = new PersistentCaster($this->projector->reveal(), $streamName);

        $this->assertEquals('foo', $caster->streamName());

        $caster->emit($event);
    }
}
