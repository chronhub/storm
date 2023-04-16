<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Message\AliasFromInflector;
use Chronhub\Storm\Message\AliasFromMap;
use Chronhub\Storm\Message\HasConstructableContent;
use Chronhub\Storm\Projector\Options\DefaultProjectionOption;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\QuerySubscription;
use Chronhub\Storm\Projector\Scheme\ProcessArrayEvent;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Stubs\Double\AnotherEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use function pcntl_signal;
use function posix_getpid;
use function posix_kill;

final class ProcessArrayQueryEventTest extends UnitTestCase
{
    private Subscription $subscription;

    private PointInTime $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $eventStore = new InMemoryEventStream();

        $streamPosition = new StreamPosition($eventStore);

        $this->subscription = new QuerySubscription(
            new DefaultProjectionOption(signal: true),
            $streamPosition,
            new PointInTime(),
        );

        $this->clock = new PointInTime();
    }

    #[DataProvider('provideMessageAlias')]
    public function testProcessEvent(?MessageAlias $messageAlias): void
    {
        $event = (new SomeEvent(['foo' => 'bar']))
            ->withHeader(EventHeader::AGGREGATE_VERSION, 1)
            ->withHeader(Header::EVENT_TIME, $this->clock->now());

        $this->subscription->streamPosition()->bind('test_stream', 0);

        $this->subscription->currentStreamName = 'test_stream';

        $this->subscription->sprint()->continue();
        $this->subscription->state()->put(['count' => 0]);

        $process = $this->newProcess($messageAlias);

        $InProgress = $process($this->subscription, $event, 1);

        $this->assertTrue($InProgress);
        $this->assertSame(1, $this->subscription->state()->get()['count']);
    }

    #[DataProvider('provideMessageAlias')]
    public function testProcessEvents(?MessageAlias $messageAlias): void
    {
        $event = (new SomeEvent(['foo' => 'bar']))
            ->withHeader(EventHeader::AGGREGATE_VERSION, 1)
            ->withHeader(Header::EVENT_TIME, $this->clock->now());

        $this->subscription->streamPosition()->bind('test_stream', 0);

        $this->subscription->currentStreamName = 'test_stream';

        $this->subscription->sprint()->continue();
        $this->subscription->state()->put(['count' => 0]);

        $process = $this->newProcess($messageAlias);

        $InProgress = $process($this->subscription, $event, 1);

        $this->assertTrue($InProgress);
        $this->assertSame(1, $this->subscription->state()->get()['count']);

        $anotherEvent = (new AnotherEvent(['foo' => 'bar']))
            ->withHeader(EventHeader::AGGREGATE_VERSION, 2)
            ->withHeader(Header::EVENT_TIME, $this->clock->now());

        $InProgress = $process($this->subscription, $anotherEvent, 2);

        $this->assertTrue($InProgress);
        $this->assertSame(2, $this->subscription->state()->get()['count']);
    }

    #[DataProvider('provideMessageAlias')]
    public function testReturnEarlyWhenMessageNameNotFoundInEventHandlers(?MessageAlias $messageAlias): void
    {
        $event = $this->fakeEvent();

        $this->subscription->streamPosition()->bind('test_stream', 0);

        $this->subscription->currentStreamName = 'test_stream';

        $this->subscription->sprint()->continue();
        $this->subscription->state()->put(['count' => 0]);

        $process = $this->newProcess($messageAlias);

        $InProgress = $process($this->subscription, $event, 1);

        $this->assertTrue($InProgress);
        $this->assertSame(0, $this->subscription->state()->get()['count']);
    }

    #[DataProvider('provideMessageAlias')]
    public function testStopGracefully(?MessageAlias $messageAlias): void
    {
        $this->assertTrue($this->subscription->option()->getSignal());

        $event = (new SomeEvent(['foo' => 'bar']))
            ->withHeader(EventHeader::AGGREGATE_VERSION, 2)
            ->withHeader(Header::EVENT_TIME, $this->clock->now());

        $this->subscription->streamPosition()->bind('test_stream', 1);

        $this->subscription->currentStreamName = 'test_stream';

        $this->subscription->sprint()->continue();
        $this->subscription->state()->put(['count' => 1]);

        pcntl_signal(SIGTERM, function () {
            $this->subscription->sprint()->stop();
        });

        posix_kill(posix_getpid(), SIGTERM);

        $process = $this->newProcess($messageAlias);

        $inProgress = $process($this->subscription, $event, 2);

        $this->assertFalse($this->subscription->sprint()->inProgress());
        $this->assertEquals(ProjectionStatus::IDLE, $this->subscription->currentStatus());
        $this->assertSame(2, $this->subscription->state()->get()['count']);
        $this->assertFalse($inProgress);
    }

    public static function provideMessageAlias(): Generator
    {
        yield [null];
        yield [new AliasFromClassName()];
        yield [new AliasFromInflector()];
        yield [new AliasFromMap([
            AnotherEvent::class => 'another-event', SomeEvent::class => 'some-event']),
        ];
    }

    private function newProcess(?MessageAlias $messageAlias): ProcessArrayEvent
    {
        if (null === $messageAlias || $messageAlias instanceof AliasFromClassName) {
            $eventHandlers = $this->provideEventHandlerWithFqn();
        } else {
            $eventHandlers = $this->provideEventHandlerWithAlias();
        }

        $this->assertEquals(ProjectionStatus::IDLE, $this->subscription->currentStatus());

        return new ProcessArrayEvent($eventHandlers, $messageAlias);
    }

    private function provideEventHandlerWithFqn(): array
    {
        return [
            SomeEvent::class => function (SomeEvent $event, array $state): array {
                $state['count']++;

                return $state;
            },

            AnotherEvent::class => function (AnotherEvent $event, array $state): array {
                $state['count']++;

                return $state;
            },
        ];
    }

    private function provideEventHandlerWithAlias(): array
    {
        return [
            'some-event' => function (SomeEvent $event, array $state): array {
                $state['count']++;

                return $state;
            },

            'another-event' => function (AnotherEvent $event, array $state): array {
                $state['count']++;

                return $state;
            },
        ];
    }

    private function fakeEvent(): DomainEvent
    {
        return new class(content : ['foo' => 'bar']) extends DomainEvent
        {
            use HasConstructableContent;
        };
    }
}
