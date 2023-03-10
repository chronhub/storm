<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\Scheme\QueryCaster;
use Chronhub\Storm\Contracts\Projector\QueryProjector;

#[CoversClass(QueryCaster::class)]
final class QueryCasterTest extends UnitTestCase
{
    private MockObject|QueryProjector $projector;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->projector = $this->createMock(QueryProjector::class);
    }

    #[Test]
    public function it_can_be_constructed(): void
    {
        $streamName = null;

        $caster = new QueryCaster($this->projector, $streamName);

        $this->assertNull($caster->streamName());
    }

    #[Test]
    public function it_return_stream_name_by_reference(): void
    {
        $streamName = null;

        $caster = new QueryCaster($this->projector, $streamName);

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

        $this->projector->expects($this->once())->method('stop');

        $caster = new QueryCaster($this->projector, $streamName);

        $this->assertEquals('foo', $caster->streamName());

        $caster->stop();
    }
}
