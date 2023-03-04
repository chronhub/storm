<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Util;

use Chronhub\Storm\Tests\Stubs\ContextStub;
use PHPUnit\Framework\MockObject\MockObject;
use Chronhub\Storm\Projector\Scheme\DetectGap;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Contracts\Projector\ProjectorOption;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;

trait ProvideMockContext
{
    protected ProjectorRepository|MockObject $repository;

    protected ProjectorOption|MockObject $option;

    protected StreamPosition|MockObject $position;

    protected EventCounter|MockObject $counter;

    protected DetectGap|MockObject $gap;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProjectorRepository::class);
        $this->option = $this->createMock(ProjectorOption::class);
        $this->position = $this->createMock(StreamPosition::class);
        $this->counter = $this->createMock(EventCounter::class);
        $this->gap = $this->createMock(DetectGap::class);
    }

    private function newContext(): ContextStub
    {
        return new ContextStub($this->option, $this->position, $this->counter, $this->gap);
    }
}
