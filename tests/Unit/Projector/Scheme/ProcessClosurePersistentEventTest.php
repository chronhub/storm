<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\AbstractEventProcessor;
use Chronhub\Storm\Projector\Scheme\ProcessClosureEvent;
use Chronhub\Storm\Reporter\DomainEvent;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractEventProcessor::class)]
#[CoversClass(ProcessClosureEvent::class)]
final class ProcessClosurePersistentEventTest extends ProcessPersistentEventTestCase
{
    protected function newProcess(): ProcessClosureEvent
    {
        // process does tamper with the subscription state only if it persists
        $this->assertEquals(ProjectionStatus::IDLE, $this->subscription->currentStatus());

        return new ProcessClosureEvent(function (DomainEvent $event, array $state): array {
            $state['count']++;

            return $state;
        });
    }
}
