<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Projector\Scheme\ReadModelCaster;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;

final class ReadModelCasterTest extends ProphecyTestCase
{
    private ObjectProphecy|QueryProjector $projector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projector = $this->prophesize(ReadModelProjector::class);
    }

    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $streamName = null;

        $caster = new ReadModelCaster($this->projector->reveal(), $streamName);

        $this->assertNull($caster->streamName());
    }

    /**
     * @test
     */
    public function it_return_stream_name_by_reference(): void
    {
        $streamName = null;

        $caster = new ReadModelCaster($this->projector->reveal(), $streamName);

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

        $caster = new ReadModelCaster($this->projector->reveal(), $streamName);

        $this->assertEquals('foo', $caster->streamName());

        $caster->stop();
    }

    /**
     * @test
     */
    public function it_access_read_model(): void
    {
        $readModel = $this->prophesize(ReadModel::class)->reveal();

        $streamName = 'foo';

        $this->projector->readModel()->willReturn($readModel)->shouldBeCalledOnce();

        $caster = new ReadModelCaster($this->projector->reveal(), $streamName);

        $this->assertEquals($readModel, $caster->readModel());
    }
}
