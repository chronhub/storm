<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Projector\EmitterCasterInterface;
use Chronhub\Storm\Contracts\Projector\EmitterProjector;
use Chronhub\Storm\Projector\Scheme\CastEmitter;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CastEmitterTest extends UnitTestCase
{
    private PointInTime $clock;

    private EmitterProjector|MockObject $projector;

    public function setUp(): void
    {
        $this->clock = new PointInTime();
        $this->projector = $this->createMock(EmitterProjector::class);
    }

    public function testInstance(): void
    {
        $streamName = null;
        $caster = new CastEmitter($this->projector, $this->clock, $streamName);

        $this->assertInstanceOf(EmitterCasterInterface::class, $caster);
        $this->assertNull($caster->streamName());
        $this->assertSame($this->clock, $caster->clock());
    }

    public function testStreamName(): void
    {
        $streamName = null;

        $caster = new CastEmitter($this->projector, $this->clock, $streamName);

        $this->assertNull($caster->streamName());

        $streamName = 'foo';

        $this->assertSame('foo', $caster->streamName());
    }

    public function testStopProjection(): void
    {
        $streamName = null;

        $this->projector->expects($this->once())->method('stop');

        $caster = new CastEmitter($this->projector, $this->clock, $streamName);

        $caster->stop();
    }

    public function testEmitEvent(): void
    {
        $streamName = 'foo';
        $event = new SomeEvent(['foo' => 'bar']);

        $this->projector->expects($this->once())->method('emit')->with($event);

        $caster = new CastEmitter($this->projector, $this->clock, $streamName);

        $caster->emit($event);
    }

    public function testLinkEventToNewStream(): void
    {
        $currentStreamName = 'baz';
        $event = new SomeEvent(['foo' => 'bar']);
        $streamName = 'bar';

        $this->projector->expects($this->once())->method('linkTo')->with($streamName, $event);

        $caster = new CastEmitter($this->projector, $this->clock, $currentStreamName);

        $caster->linkTo($streamName, $event);
    }
}
