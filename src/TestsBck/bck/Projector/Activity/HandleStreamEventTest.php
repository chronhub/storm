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
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Activity\LoadStreamsToRemove;
use Chronhub\Storm\Projector\Options\InMemoryOption;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\QueryProjectorScope;
use Chronhub\Storm\Projector\Scheme\StreamManager;
use Chronhub\Storm\Projector\Subscription\QuerySubscription;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\DetermineStreamCategory;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HandleStreamEvent::class)]
class HandleStreamEventTest extends UnitTestCase
{
    private Subscription $subscription;

    private Chronicler $chronicler;

    private LoadStreamsToRemove $loader;

    protected function setUp(): void
    {
        parent::setUp();

        $eventStore = new InMemoryEventStream();

        $this->subscription = new QuerySubscription(
            new InMemoryOption(),
            new StreamManager($eventStore),
            new PointInTime()
        );

        $this->chronicler = new StandaloneInMemoryChronicler(
            $eventStore,
            new DetermineStreamCategory()
        );

        $this->loader = new LoadStreamsToRemove($this->chronicler);
    }

    public function testHandleEvent(): void
    {
        $events = (new SomeEvent(['foo' => 'bar']))->withHeader(EventHeader::AGGREGATE_VERSION, 1);
        $stream = new Stream(new StreamName('test_stream'), [$events]);

        $this->chronicler->firstCommit($stream);

        $this->subscription->streamManager()->bind('test_stream', 0);
        $this->assertSame(0, $this->subscription->streamManager()->jsonSerialize()['test_stream']);
        $this->assertNull($this->subscription->currentStreamName());

        $context = new Context();
        $context->withQueryFilter($this->dummyQueryFilter());
        $context->fromStreams('test_stream');

        $eventHandled = false;
        $context->when(function (DomainEvent $event) use (&$eventHandled): void {
            TestCase::assertInstanceOf(SomeEvent::class, $event);
            $eventHandled = true;
        });

        $caster = new QueryProjectorScope(
            $this->createMock(QueryProjector::class),
            new PointInTime(),
            fn () => $this->subscription->currentStreamName()
        );

        $this->subscription->compose($context, $caster, false);

        $next = static fn (Subscription $subscription) => true;

        $activity = new HandleStreamEvent($this->loader);

        $this->assertTrue($activity($this->subscription, $next));
        $this->assertTrue($eventHandled);
        $this->assertSame('test_stream', $this->subscription->currentStreamName());
        $this->assertSame(1, $this->subscription->streamManager()->jsonSerialize()['test_stream']);
    }

    public function testManyStreams(): void
    {
        $events = (new SomeEvent(['foo' => 'bar']))
            ->withHeader(EventHeader::AGGREGATE_VERSION, 1)
            ->withHeader(Header::EVENT_TIME, (new PointInTime())->now());

        $firstStream = new Stream(new StreamName('first_stream'), [$events]);
        $secondStream = new Stream(new StreamName('second_stream'), [$events]);

        $this->chronicler->firstCommit($firstStream);
        $this->chronicler->firstCommit($secondStream);

        $this->subscription->streamManager()->bind('first_stream', 0);
        $this->subscription->streamManager()->bind('second_stream', 0);
        $this->assertSame(0, $this->subscription->streamManager()->jsonSerialize()['first_stream']);
        $this->assertSame(0, $this->subscription->streamManager()->jsonSerialize()['second_stream']);
        $this->assertNull($this->subscription->currentStreamName());

        $context = new Context();
        $context->withQueryFilter($this->dummyQueryFilter());
        $context->fromAll();

        $countEvents = 0;
        $context->when(function (DomainEvent $event) use (&$countEvents): void {
            /** @var QueryProjectorScopeInterface $this */
            TestCase::assertInstanceOf(SomeEvent::class, $event);

            if ($countEvents === 0) {
                TestCase::assertSame('first_stream', $this->streamName());
            } else {
                TestCase::assertSame('second_stream', $this->streamName());
            }

            $countEvents++;
        });

        $caster = new QueryProjectorScope(
            $this->createMock(QueryProjector::class),
            new PointInTime(),
            fn () => $this->subscription->currentStreamName()
        );

        $this->subscription->compose($context, $caster, false);

        $next = static fn (Subscription $subscription) => true;

        $activity = new HandleStreamEvent($this->loader);

        $this->assertTrue($activity($this->subscription, $next));

        $this->assertSame(2, $countEvents);
        $this->assertSame('second_stream', $this->subscription->currentStreamName());
        $this->assertSame(1, $this->subscription->streamManager()->jsonSerialize()['first_stream']);
        $this->assertSame(1, $this->subscription->streamManager()->jsonSerialize()['second_stream']);
    }

    private function dummyQueryFilter(): InMemoryQueryFilter
    {
        return new class implements InMemoryQueryFilter
        {
            public function apply(): callable
            {
                return static fn ($event) => $event;
            }

            public function orderBy(): string
            {
                return 'asc';
            }
        };
    }
}
