<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Chronicler\InMemory\StandaloneInMemoryChronicler;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Projector\EmitterSubscription;
use Chronhub\Storm\Projector\Options\DefaultProjectionOption;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\AbstractEventProcessor;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\ProcessClosureEvent;
use Chronhub\Storm\Projector\Scheme\StreamGapDetector;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\DetermineStreamCategory;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use function pcntl_signal;
use function posix_getpid;
use function posix_kill;

#[CoversClass(AbstractEventProcessor::class)]
#[CoversClass(ProcessClosureEvent::class)]
final class ProcessClosurePersistentEventTest extends UnitTestCase
{
    private ProjectionRepositoryInterface|MockObject $repository;

    private PersistentSubscriptionInterface $subscription;

    private Chronicler $chronicler;

    private PointInTime $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $eventStore = new InMemoryEventStream();

        $this->chronicler = new StandaloneInMemoryChronicler(
            $eventStore,
            new DetermineStreamCategory()
        );

        $streamPosition = new StreamPosition($eventStore);

        $this->repository = $this->createMock(ProjectionRepositoryInterface::class);
        $this->subscription = new EmitterSubscription(
            $this->repository,
            new DefaultProjectionOption(signal: true),
            $streamPosition,
            new EventCounter(2),
            new StreamGapDetector($streamPosition, new PointInTime(), [10]),
            new PointInTime(),
            $this->chronicler
        );

        $this->clock = new PointInTime();
    }

    // todo test gap detection with no retries and detection window
    public function testProcessEvent(): void
    {
        $event = (new SomeEvent(['foo' => 'bar']))
            ->withHeader(EventHeader::AGGREGATE_VERSION, 1)
            ->withHeader(Header::EVENT_TIME, $this->clock->now());

        $this->subscription->streamPosition()->bind('test_stream', 0);

        $this->subscription->currentStreamName = 'test_stream';

        $this->assertFalse(
            $this->subscription->gap()->detect(
                'test_stream', 1, $event->header(Header::EVENT_TIME)
            )
        );

        $this->assertTrue($this->subscription->eventCounter()->isReset());

        $this->subscription->sprint()->continue();
        $this->subscription->state()->put(['count' => 0]);

        $process = $this->newProcess();

        $InProgress = $process($this->subscription, $event, 1);

        $this->assertTrue($InProgress);
        $this->assertFalse($this->subscription->eventCounter()->isReset());
        $this->assertSame(1, $this->subscription->state()->get()['count']);
    }

    public function testReturnEarlyWhenGapDetected(): void
    {
        $event = (new SomeEvent(['foo' => 'bar']))
            ->withHeader(EventHeader::AGGREGATE_VERSION, 1)
            ->withHeader(Header::EVENT_TIME, $this->clock->now());

        $this->subscription->streamPosition()->bind('test_stream', 5);

        $this->subscription->currentStreamName = 'test_stream';

        $this->assertTrue(
            $this->subscription->gap()->detect(
                'test_stream', 1, $event->header(Header::EVENT_TIME)
            )
        );

        $this->repository->expects($this->never())->method('persist');
        $this->repository->expects($this->never())->method('loadStatus');

        $process = $this->newProcess();

        $InProgress = $process($this->subscription, $event, 1);

        $this->assertFalse($InProgress);
        $this->assertTrue($this->subscription->eventCounter()->isReset());
        $this->assertEmpty($this->subscription->state()->get());
    }

    #[DataProvider('provideStatusWhichKeepRunningWhenCounterIsReached')]
    public function testPersistPositionAndStateWhenCounterIsReached(ProjectionStatus $discloseStatus): void
    {
        $event = (new SomeEvent(['foo' => 'bar']))
            ->withHeader(EventHeader::AGGREGATE_VERSION, 2)
            ->withHeader(Header::EVENT_TIME, $this->clock->now());

        $this->subscription->streamPosition()->bind('test_stream', 1);

        $this->subscription->currentStreamName = 'test_stream';

        $this->assertFalse(
            $this->subscription->gap()->detect(
                'test_stream', 2, $event->header(Header::EVENT_TIME)
            )
        );

        $this->assertTrue($this->subscription->eventCounter()->isReset());
        $this->subscription->eventCounter()->increment();

        $this->subscription->sprint()->continue();
        $this->subscription->state()->put(['count' => 1]);

        $this->repository
            ->expects($this->once())
            ->method('persist')
            ->with(['test_stream' => 2], ['count' => 2]);

        $this->repository
            ->expects($this->once())
            ->method('loadStatus')
            ->willReturn($discloseStatus);

        $process = $this->newProcess();

        $inProgress = $process($this->subscription, $event, 2);

        $this->assertTrue($this->subscription->sprint()->inProgress());
        $this->assertEquals($discloseStatus, $this->subscription->currentStatus());
        $this->assertTrue($this->subscription->eventCounter()->isReset());
        $this->assertSame(2, $this->subscription->state()->get()['count']);
        $this->assertTrue($inProgress);
    }

    #[DataProvider('provideStatusWhichStopRunningWhenCounterIsReached')]
    public function testPersistPositionAndStateWhenCounterIsReachedAndStopProjection(ProjectionStatus $discloseStatus): void
    {
        $event = (new SomeEvent(['foo' => 'bar']))
            ->withHeader(EventHeader::AGGREGATE_VERSION, 2)
            ->withHeader(Header::EVENT_TIME, $this->clock->now());

        $this->subscription->streamPosition()->bind('test_stream', 1);

        $this->subscription->currentStreamName = 'test_stream';

        $this->assertFalse(
            $this->subscription->gap()->detect(
                'test_stream', 2, $event->header(Header::EVENT_TIME)
            )
        );

        $this->assertTrue($this->subscription->eventCounter()->isReset());
        $this->subscription->eventCounter()->increment();

        $this->subscription->sprint()->continue();
        $this->subscription->state()->put(['count' => 1]);

        $this->repository
            ->expects($this->once())
            ->method('persist')
            ->with(['test_stream' => 2], ['count' => 2]);

        $this->repository
            ->expects($this->once())
            ->method('loadStatus')
            ->willReturn($discloseStatus);

        $process = $this->newProcess();

        $InProgress = $process($this->subscription, $event, 2);

        $this->assertFalse($this->subscription->sprint()->inProgress());
        $this->assertEquals($discloseStatus, $this->subscription->currentStatus());
        $this->assertTrue($this->subscription->eventCounter()->isReset());
        $this->assertSame(2, $this->subscription->state()->get()['count']);
        $this->assertFalse($InProgress);
    }

    public function testStopGracefully(): void
    {
        $this->assertTrue($this->subscription->option()->getSignal());

        $event = (new SomeEvent(['foo' => 'bar']))
            ->withHeader(EventHeader::AGGREGATE_VERSION, 2)
            ->withHeader(Header::EVENT_TIME, $this->clock->now());

        $this->subscription->streamPosition()->bind('test_stream', 1);

        $this->subscription->currentStreamName = 'test_stream';

        $this->assertFalse(
            $this->subscription->gap()->detect(
                'test_stream', 2, $event->header(Header::EVENT_TIME)
            )
        );

        $this->assertTrue($this->subscription->eventCounter()->isReset());
        $this->subscription->eventCounter()->increment();

        $this->subscription->sprint()->continue();
        $this->subscription->state()->put(['count' => 1]);

        $this->repository
            ->expects($this->once())
            ->method('persist')
            ->with(['test_stream' => 2], ['count' => 2]);

        pcntl_signal(SIGTERM, function () {
            $this->repository
                ->expects($this->once())
                ->method('loadStatus')
                ->willReturn(ProjectionStatus::STOPPING);
        });

        posix_kill(posix_getpid(), SIGTERM);

        $process = $this->newProcess();

        $inProgress = $process($this->subscription, $event, 2);

        $this->assertFalse($this->subscription->sprint()->inProgress());
        $this->assertEquals(ProjectionStatus::STOPPING, $this->subscription->currentStatus());
        $this->assertTrue($this->subscription->eventCounter()->isReset());
        $this->assertSame(2, $this->subscription->state()->get()['count']);
        $this->assertFalse($inProgress);
    }

    public static function provideStatusWhichKeepRunningWhenCounterIsReached(): Generator
    {
        yield [ProjectionStatus::RUNNING];
        yield [ProjectionStatus::IDLE];
    }

    public static function provideStatusWhichStopRunningWhenCounterIsReached(): Generator
    {
        yield [ProjectionStatus::RESETTING];
        yield [ProjectionStatus::STOPPING];
        yield [ProjectionStatus::DELETING];
        yield [ProjectionStatus::DELETING_WITH_EMITTED_EVENTS];
    }

    private function newProcess(): ProcessClosureEvent
    {
        return new ProcessClosureEvent(function (DomainEvent $event, array $state): array {
            $state['count']++;

            return $state;
        });
    }
}
