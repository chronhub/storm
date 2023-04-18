<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Chronicler\InMemory\StandaloneInMemoryChronicler;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\EmitterProjector;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Activity\LoadStreams;
use Chronhub\Storm\Projector\EmitterSubscription;
use Chronhub\Storm\Projector\Options\InMemoryProjectionOption;
use Chronhub\Storm\Projector\Scheme\CastEmitter;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamGapDetector;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Stream\DetermineStreamCategory;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HandleStreamEvent::class)]
final class HandlePersistedStreamEventTest extends UnitTestCase
{
    private Subscription $subscription;

    private Chronicler $chronicler;

    private LoadStreams $loader;

    protected function setUp(): void
    {
        parent::setUp();

        $eventStore = new InMemoryEventStream();

        $this->chronicler = new StandaloneInMemoryChronicler(
            $eventStore,
            new DetermineStreamCategory()
        );

        $streamPosition = new StreamPosition($eventStore);

        $this->subscription = new EmitterSubscription(
            $this->createMock(ProjectionRepositoryInterface::class),
            new InMemoryProjectionOption(),
            $streamPosition,
            new EventCounter(10),
            new StreamGapDetector($streamPosition, new PointInTime(), [10]),
            new PointInTime(),
            $this->chronicler
        );

        $this->loader = new LoadStreams($this->chronicler);
    }

    public function testReturnEarlyWhenGapDetected(): void
    {
        $now = (new PointInTime())->now();

        $event = (new SomeEvent(['foo' => 'bar']))
            ->withHeader(EventHeader::AGGREGATE_VERSION, 1)
            ->withHeader(Header::EVENT_TIME, $now);

        $eventWithGap = (new SomeEvent(['foo' => 'bar']))
            ->withHeader(EventHeader::AGGREGATE_VERSION, 8)
            ->withHeader(Header::EVENT_TIME, $now);

        $firstStream = new Stream(new StreamName('first_stream'), [$eventWithGap]);
        $secondStream = new Stream(new StreamName('second_stream'), [$event]);

        $this->chronicler->firstCommit($firstStream);
        $this->chronicler->firstCommit($secondStream);

        $this->subscription->streamPosition()->bind('first_stream', 6);
        $this->subscription->streamPosition()->bind('second_stream', 0);

        $this->assertSame(6, $this->subscription->streamPosition()->all()['first_stream']);
        $this->assertSame(0, $this->subscription->streamPosition()->all()['second_stream']);
        $this->assertNull($this->subscription->currentStreamName);

        $context = new Context();
        $context->withQueryFilter($this->dummyQueryFilter());
        $context->fromStreams('first_stream', 'second_stream');

        $countEvents = 0;
        $context->whenAny(function () use (&$countEvents): void {
            $countEvents++;
        });

        $caster = new CastEmitter(
            $this->createMock(EmitterProjector::class),
            new PointInTime(),
            $this->subscription->currentStreamName
        );

        $this->subscription->compose($context, $caster, false);

        $next = static fn (Subscription $subscription) => true;

        $activity = new HandleStreamEvent($this->loader);

        $this->assertTrue($activity($this->subscription, $next));

        $this->assertSame(0, $countEvents);
        $this->assertSame('first_stream', $this->subscription->currentStreamName);

        $this->assertSame(6, $this->subscription->streamPosition()->all()['first_stream']);
        $this->assertSame(0, $this->subscription->streamPosition()->all()['second_stream']);
    }

    private function dummyQueryFilter(): InMemoryQueryFilter|ProjectionQueryFilter
    {
        return new class implements InMemoryQueryFilter, ProjectionQueryFilter
        {
            private int $position = 0;

            public function setCurrentPosition(int $streamPosition): void
            {
                $this->position = $streamPosition;
            }

            public function apply(): callable
            {
                return fn ($event) => $event->header(EventHeader::INTERNAL_POSITION) >= $this->position;
            }

            public function orderBy(): string
            {
                return 'asc';
            }
        };
    }
}
