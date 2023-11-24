<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorScopeInterface;
use Chronhub\Storm\Projector\Scheme\ReadModelProjectorScope;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(ReadModelProjectorScope::class)]
final class CastReadModelTest extends UnitTestCase
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
        $caster = new ReadModelProjectorScope($this->projector, $this->clock, $streamName);

        $this->assertInstanceOf(ReadModelProjectorScopeInterface::class, $caster);
        $this->assertNull($caster->streamName());
        $this->assertSame($this->clock, $caster->clock());
    }

    public function testStreamName(): void
    {
        $streamName = null;

        $caster = new ReadModelProjectorScope($this->projector, $this->clock, $streamName);

        $this->assertNull($caster->streamName());

        $streamName = 'foo';

        $this->assertSame('foo', $caster->streamName());
    }

    public function testAccessToReadModel(): void
    {
        $streamName = null;

        $readModel = $this->createMock(ReadModel::class);
        $this->projector->expects($this->once())->method('readModel')->willReturn($readModel);

        $caster = new ReadModelProjectorScope($this->projector, $this->clock, $streamName);

        $this->assertSame($readModel, $caster->readModel());
    }

    public function testStopProjection(): void
    {
        $streamName = null;

        $this->projector->expects($this->once())->method('stop');

        $caster = new ReadModelProjectorScope($this->projector, $this->clock, $streamName);

        $caster->stop();
    }
}
