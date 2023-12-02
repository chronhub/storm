<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Uses;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Projector\Subscription\GenericSubscription;
use Chronhub\Storm\Tests\Factory\GenericSubscriptionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use tests\TestCase;

trait TestingGenericSubscription
{
    protected GenericSubscription $subscription;

    protected ProjectionOption|MockObject $option;

    protected StreamManagerInterface|MockObject $streamManager;

    protected SystemClock|MockObject $clock;

    protected Chronicler|MockObject $chronicler;

    protected function setUpGenericSubscription(): void
    {
        /** @var TestCase $this */
        $this->subscription = GenericSubscriptionFactory::mock(
            $this->option = $this->createMock(ProjectionOption::class),
            $this->streamManager = $this->createMock(StreamManagerInterface::class),
            $this->clock = $this->createMock(SystemClock::class),
            $this->chronicler = $this->createMock(Chronicler::class)
        );
    }
}
