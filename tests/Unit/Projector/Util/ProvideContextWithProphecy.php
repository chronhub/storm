<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Util;

use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Tests\Stubs\ContextStub;
use Chronhub\Storm\Projector\Scheme\DetectGap;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Contracts\Projector\ProjectorOption;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;

trait ProvideContextWithProphecy
{
    protected ProjectorRepository|ObjectProphecy $repository;

    protected ProjectorOption|ObjectProphecy $option;

    protected StreamPosition|ObjectProphecy $position;

    protected ObjectProphecy|EventCounter $counter;

    protected DetectGap|ObjectProphecy $gap;

    protected function setUp(): void
    {
        $this->repository = $this->prophesize(ProjectorRepository::class);
        $this->option = $this->prophesize(ProjectorOption::class);
        $this->position = $this->prophesize(StreamPosition::class);
        $this->counter = $this->prophesize(EventCounter::class);
        $this->gap = $this->prophesize(DetectGap::class);
    }

    private function newContext(): ContextStub
    {
        return new ContextStub(
            $this->option->reveal(),
            $this->position->reveal(),
            $this->counter->reveal(),
            $this->gap->reveal()
        );
    }
}
