<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Projector\Scheme\ReadModelCaster;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;

final class ReadModelCasterTest extends UnitTestCase
{
    private MockObject|ReadModelProjector $projector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projector = $this->createMock(ReadModelProjector::class);
    }

    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $streamName = null;

        $caster = new ReadModelCaster($this->projector, $streamName);

        $this->assertNull($caster->streamName());
    }

    /**
     * @test
     */
    public function it_return_stream_name_by_reference(): void
    {
        $streamName = null;

        $caster = new ReadModelCaster($this->projector, $streamName);

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

        $this->projector->expects($this->once())->method('stop');

        $caster = new ReadModelCaster($this->projector, $streamName);

        $this->assertEquals('foo', $caster->streamName());

        $caster->stop();
    }

    /**
     * @test
     */
    public function it_access_read_model(): void
    {
        $readModel = $this->createMock(ReadModel::class);

        $streamName = 'foo';

        $this->projector->expects($this->once())->method('readModel')->willReturn($readModel);

        $caster = new ReadModelCaster($this->projector, $streamName);

        $this->assertEquals($readModel, $caster->readModel());
    }
}
