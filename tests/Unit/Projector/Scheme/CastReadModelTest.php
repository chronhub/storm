<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelCasterInterface;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Projector\Scheme\CastReadModel;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(CastReadModel::class)]
class CastReadModelTest extends UnitTestCase
{
    private PointInTime $clock;

    private ReadModelProjector|MockObject $projector;

    public function setUp(): void
    {
        $this->clock = new PointInTime();
        $this->projector = $this->createMock(ReadModelProjector::class);
    }

    public function testInstance(): void
    {
        $streamName = null;
        $caster = new CastReadModel($this->projector, $this->clock, $streamName);

        $this->assertInstanceOf(ReadModelCasterInterface::class, $caster);
        $this->assertNull($caster->streamName());
        $this->assertSame($this->clock, $caster->clock());
    }

    public function testStreamName(): void
    {
        $streamName = null;

        $caster = new CastReadModel($this->projector, $this->clock, $streamName);

        $this->assertNull($caster->streamName());

        $streamName = 'foo';

        $this->assertSame('foo', $caster->streamName());
    }

    public function testAccessToReadModel(): void
    {
        $streamName = null;

        $readModel = $this->createMock(ReadModel::class);
        $this->projector->expects($this->once())->method('readModel')->willReturn($readModel);

        $caster = new CastReadModel($this->projector, $this->clock, $streamName);

        $this->assertSame($readModel, $caster->readModel());
    }

    public function testStopProjection(): void
    {
        $streamName = null;

        $this->projector->expects($this->once())->method('stop');

        $caster = new CastReadModel($this->projector, $this->clock, $streamName);

        $caster->stop();
    }
}
