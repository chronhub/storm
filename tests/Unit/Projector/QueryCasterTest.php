<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Projector\Scheme\QueryCaster;
use Chronhub\Storm\Contracts\Projector\QueryProjector;

final class QueryCasterTest extends ProphecyTestCase
{
    private ObjectProphecy|QueryProjector $projector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projector = $this->prophesize(QueryProjector::class);
    }

    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $streamName = null;

        $caster = new QueryCaster($this->projector->reveal(), $streamName);

        $this->assertNull($caster->streamName());
    }

    /**
     * @test
     */
    public function it_return_stream_name_by_reference(): void
    {
        $streamName = null;

        $caster = new QueryCaster($this->projector->reveal(), $streamName);

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

        $caster = new QueryCaster($this->projector->reveal(), $streamName);

        $this->assertEquals('foo', $caster->streamName());

        $caster->stop();
    }
}
