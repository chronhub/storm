<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Message\HasConstructableContent;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\ProcessArrayEvent;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProcessArrayEvent::class)]
final class ProcessArrayPersistentEventTest extends ProcessPersistentEventTestCase
{
    public function testPersistPositionAndStateWhenCounterIsReachedAndEventNotFound(): void
    {
        $this->assertEquals(ProjectionStatus::IDLE, $this->subscription->currentStatus());

        // event found increment the event counter and position
        $event = (new SomeEvent(['foo' => 'bar']))
            ->withHeader(EventHeader::AGGREGATE_VERSION, 1)
            ->withHeader(Header::EVENT_TIME, $this->clock->now());

        $this->subscription->streamManager()->bind('test_stream', 0);

        $this->subscription->currentStreamName = 'test_stream';

        $this->assertFalse(
            $this->subscription->gap()->detect(
                'test_stream', 1, $event->header(Header::EVENT_TIME)
            )
        );

        $this->assertTrue($this->subscription->eventCounter()->isReset());

        $this->subscription->sprint()->continue();
        $this->subscription->state()->put(['count' => 0]);

        // event not found increment the event counter and position
        $this->repository
            ->expects($this->once())
            ->method('persist')
            ->with(['test_stream' => 2], ['count' => 1]);

        $this->repository
            ->expects($this->once())
            ->method('loadStatus')
            ->willReturn(ProjectionStatus::RUNNING);

        $process = $this->newProcess();

        $process($this->subscription, $event, 1);

        $this->assertTrue($this->subscription->sprint()->inProgress());

        $fakeEvent = $this->fakeEvent()
            ->withHeader(EventHeader::AGGREGATE_VERSION, 2)
            ->withHeader(Header::EVENT_TIME, $this->clock->now());

        $this->assertFalse(
            $this->subscription->gap()->detect(
                'test_stream', 2, $event->header(Header::EVENT_TIME)
            )
        );

        $inProgress = $process($this->subscription, $fakeEvent, 2);

        $this->assertTrue($this->subscription->sprint()->inProgress());
        $this->assertEquals(ProjectionStatus::RUNNING, $this->subscription->currentStatus());
        $this->assertTrue($this->subscription->eventCounter()->isReset());
        $this->assertSame(1, $this->subscription->state()->get()['count']);
        $this->assertTrue($inProgress);
    }

    protected function newProcess(): ProcessArrayEvent
    {
        $eventHandlers = [
            SomeEvent::class => function (DomainEvent $event, array $state): array {
                $state['count']++;

                return $state;
            },
        ];

        // process does tamper with the subscription status only if it persists
        $this->assertEquals(ProjectionStatus::IDLE, $this->subscription->currentStatus());

        return new ProcessArrayEvent($eventHandlers, null);
    }

    private function fakeEvent(): DomainEvent
    {
        return new class(content : ['foo' => 'bar']) extends DomainEvent
        {
            use HasConstructableContent;
        };
    }
}
