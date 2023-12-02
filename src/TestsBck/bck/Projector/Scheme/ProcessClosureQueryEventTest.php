<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Options\DefaultOption;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\AbstractEventProcessor;
use Chronhub\Storm\Projector\Scheme\ProcessClosureEvent;
use Chronhub\Storm\Projector\Scheme\StreamManager;
use Chronhub\Storm\Projector\Subscription\QuerySubscription;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use function pcntl_signal;
use function posix_getpid;
use function posix_kill;

#[CoversClass(AbstractEventProcessor::class)]
#[CoversClass(ProcessClosureEvent::class)]
final class ProcessClosureQueryEventTest extends UnitTestCase
{
    private Subscription $subscription;

    private PointInTime $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $eventStore = new InMemoryEventStream();

        $streamPosition = new StreamManager($eventStore);

        $this->subscription = new QuerySubscription(
            new DefaultOption(signal: true),
            $streamPosition,
            new PointInTime(),
        );

        $this->clock = new PointInTime();
    }

    public function testProcessEvent(): void
    {
        $event = (new SomeEvent(['foo' => 'bar']))
            ->withHeader(EventHeader::AGGREGATE_VERSION, 1)
            ->withHeader(Header::EVENT_TIME, $this->clock->now());

        $this->subscription->streamManager()->bind('test_stream', 0);

        $this->subscription->currentStreamName = 'test_stream';

        $this->subscription->sprint()->continue();
        $this->subscription->state()->put(['count' => 0]);

        $process = $this->newProcess();

        $InProgress = $process($this->subscription, $event, 1);

        $this->assertTrue($InProgress);
        $this->assertSame(1, $this->subscription->state()->get()['count']);
    }

    public function testStopGracefully(): void
    {
        $this->assertTrue($this->subscription->option()->getSignal());

        $event = (new SomeEvent(['foo' => 'bar']))
            ->withHeader(EventHeader::AGGREGATE_VERSION, 2)
            ->withHeader(Header::EVENT_TIME, $this->clock->now());

        $this->subscription->streamManager()->bind('test_stream', 1);

        $this->subscription->currentStreamName = 'test_stream';

        $this->subscription->sprint()->continue();
        $this->subscription->state()->put(['count' => 1]);

        pcntl_signal(SIGTERM, function () {
            $this->subscription->sprint()->stop();
        });

        posix_kill(posix_getpid(), SIGTERM);

        $process = $this->newProcess();

        $inProgress = $process($this->subscription, $event, 2);

        $this->assertFalse($this->subscription->sprint()->inProgress());
        $this->assertEquals(ProjectionStatus::IDLE, $this->subscription->currentStatus());
        $this->assertSame(2, $this->subscription->state()->get()['count']);
        $this->assertFalse($inProgress);
    }

    private function newProcess(): ProcessClosureEvent
    {
        $this->assertEquals(ProjectionStatus::IDLE, $this->subscription->currentStatus());

        return new ProcessClosureEvent(function (DomainEvent $event, array $state): array {
            $state['count']++;

            return $state;
        });
    }
}
