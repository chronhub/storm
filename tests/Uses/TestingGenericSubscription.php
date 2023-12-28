<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Uses;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\ChroniclerDecorator;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\CheckpointRecognition;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Projector\Subscription\Beacon;
use PHPUnit\Framework\MockObject\MockObject;
use tests\TestCase;

trait TestingGenericSubscription
{
    protected Beacon $subscription;

    protected ContextReader|MockObject $context;

    protected CheckpointRecognition|MockObject $streamManager;

    protected SystemClock|MockObject $clock;

    protected ProjectionOption|MockObject $option;

    protected Chronicler|MockObject $chronicler;

    protected function setUpGenericSubscription(): void
    {
        /** @var TestCase $this */
        $this->context = $this->createMock(ContextReader::class);
        $this->streamManager = $this->createMock(CheckpointRecognition::class);
        $this->clock = $this->createMock(SystemClock::class);
        $this->option = $this->createMock(ProjectionOption::class);
        $this->chronicler = $this->createMock(Chronicler::class);

        $this->subscription = new Beacon(
            $this->context, $this->streamManager, $this->clock,
            $this->option, $this->chronicler,
        );
    }

    protected function setUpAndAndAssertInnerMostChronicler(): void
    {
        /** @var TestCase $this */
        $this->context = $this->createMock(ContextReader::class);
        $this->streamManager = $this->createMock(CheckpointRecognition::class);
        $this->clock = $this->createMock(SystemClock::class);
        $this->option = $this->createMock(ProjectionOption::class);

        $chronicler = $this->createMock(Chronicler::class);
        $innerChronicler = $this->createMock(ChroniclerDecorator::class);
        $innerChronicler->expects($this->once())->method('innerChronicler')->willReturn($chronicler);

        $this->subscription = new Beacon(
            $this->context, $this->streamManager, $this->clock,
            $this->option, $innerChronicler,
        );

        expect($this->subscription->chronicler())->toBe($chronicler);
    }
}
