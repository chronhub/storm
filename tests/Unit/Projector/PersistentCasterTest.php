<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Projector\Scheme\PersistentCaster;
use Chronhub\Storm\Contracts\Projector\ProjectionProjector;

#[CoversClass(PersistentCaster::class)]
final class PersistentCasterTest extends UnitTestCase
{
    private MockObject|ProjectionProjector $projector;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->projector = $this->createMock(ProjectionProjector::class);
    }

    #[Test]
    public function it_can_be_constructed(): void
    {
        $streamName = null;

        $caster = new PersistentCaster($this->projector, $streamName);

        $this->assertNull($caster->streamName());
    }

    #[Test]
    public function it_return_stream_name_by_reference(): void
    {
        $streamName = null;

        $caster = new PersistentCaster($this->projector, $streamName);

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

    #[Test]
    public function it_can_stop_projection(): void
    {
        $streamName = 'foo';

        $this->projector
            ->expects($this->once())
            ->method('stop');

        $caster = new PersistentCaster($this->projector, $streamName);

        $this->assertEquals('foo', $caster->streamName());

        $caster->stop();
    }

    #[Test]
    public function it_link_event_to_new_stream(): void
    {
        $streamName = 'foo';
        $event = SomeEvent::fromContent(['name' => 'steph bug']);

        $this->projector->expects($this->once())
            ->method('linkTo')
            ->with('foo', $event);

        $caster = new PersistentCaster($this->projector, $streamName);

        $this->assertEquals('foo', $caster->streamName());

        $caster->linkTo('foo', $event);
    }

    #[Test]
    public function it_emit_event(): void
    {
        $streamName = 'foo';
        $event = SomeEvent::fromContent(['name' => 'steph bug']);

        $this->projector->expects($this->once())
            ->method('emit')
            ->with($event);

        $caster = new PersistentCaster($this->projector, $streamName);

        $this->assertEquals('foo', $caster->streamName());

        $caster->emit($event);
    }
}
