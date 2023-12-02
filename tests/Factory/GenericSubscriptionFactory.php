<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Factory;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Projector\Subscription\GenericSubscription;
use PHPUnit\Framework\MockObject\MockObject;

final class GenericSubscriptionFactory
{
    public static function mock(
        ProjectionOption&MockObject $option,
        StreamManagerInterface&MockObject $streamManager,
        SystemClock&MockObject $clock,
        Chronicler&MockObject $chronicler
    ): GenericSubscription {
        return new GenericSubscription($option, $streamManager, $clock, $chronicler);
    }
}
