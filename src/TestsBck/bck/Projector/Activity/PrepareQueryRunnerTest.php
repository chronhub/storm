<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Activity\RiseQueryProjection;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\StreamManager;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RiseQueryProjection::class)]
final class PrepareQueryRunnerTest extends UnitTestCase
{
    public function testInvoke(): void
    {
        $subscription = $this->createMock(Subscription::class);

        $context = new Context();
        $context->fromStreams('stream_1', 'stream_2');

        $subscription->expects($this->once())->method('context')->willReturn($context);
        $streamPosition = new StreamManager(new InMemoryEventStream());
        $streamPosition->bind('stream_1', 10);
        $streamPosition->bind('stream_2', 100);

        $subscription
            ->expects($this->exactly(2))
            ->method('streamManager')
            ->willReturn($streamPosition);

        $next = function (Subscription $subscription) {
            $this->assertSame(
                ['stream_1' => 10, 'stream_2' => 100],
                $subscription->streamManager()->jsonSerialize()
            );

            return true;
        };

        $prepareQueryRunner = new RiseQueryProjection();

        $result = $prepareQueryRunner($subscription, $next);

        $this->assertTrue($result);
    }
}
