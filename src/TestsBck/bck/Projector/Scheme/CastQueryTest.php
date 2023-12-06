<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScopeInterface;
use Chronhub\Storm\Projector\Scheme\QueryProjectorScope;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(QueryProjectorScope::class)]
final class CastQueryTest extends UnitTestCase
{
    private PointInTime $clock;

    private QueryProjector|MockObject $projector;

    public function setUp(): void
    {
        $this->clock = new PointInTime();
        $this->projector = $this->createMock(QueryProjector::class);
    }

    public function testInstance(): void
    {
        $streamName = null;
        $caster = new QueryProjectorScope($this->projector, $this->clock, $streamName);

        $this->assertInstanceOf(QueryProjectorScopeInterface::class, $caster);
        $this->assertNull($caster->streamName());
        $this->assertSame($this->clock, $caster->clock());
    }

    public function testStreamName(): void
    {
        $streamName = null;

        $caster = new QueryProjectorScope($this->projector, $this->clock, $streamName);

        $this->assertNull($caster->streamName());

        $streamName = 'foo';

        $this->assertSame('foo', $caster->streamName());
    }

    public function testStopProjection(): void
    {
        $streamName = null;

        $this->projector->expects($this->once())->method('stop');

        $caster = new QueryProjectorScope($this->projector, $this->clock, $streamName);

        $caster->stop();
    }
}
