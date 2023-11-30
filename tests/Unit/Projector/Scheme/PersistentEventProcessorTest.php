<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use LogicException;

use function pcntl_signal;
use function posix_getpid;
use function posix_kill;

beforeEach(function () {
    $this->subscription = $this->createMock(PersistentSubscriptionInterface::class);
    $this->option = $this->createMock(ProjectionOption::class);
    $this->streamManager = $this->createMock(StreamManagerInterface::class);
    $this->context = $this->createMock(ContextReaderInterface::class);

    $this->subscription->expects($this->once())->method('option')->willReturn($this->option);
    $this->subscription->expects($this->any())->method('streamManager')->willReturn($this->streamManager);
    $this->state = new ProjectionState();
    $this->eventCounter = new EventCounter(5);
    $this->sprint = new Sprint();
    $this->event = SomeEvent::fromContent(['name' => 'steph'])->withHeader(Header::EVENT_TIME, 'some_event_time');
});

dataset('sprint in', ['in progress' => true, 'stopped' => false]);

$assertEventProcessed = function (bool $inProgress) {
    $this->context->expects($this->exactly(1))->method('userState')->willReturn(fn () => []);
    $this->subscription->expects($this->any())->method('context')->willReturn($this->context);
    $this->subscription->expects($this->once())->method('eventCounter')->willReturn($this->eventCounter);
    $this->subscription->expects($this->once())->method('persistWhenCounterIsReached');
    $this->subscription->expects($this->once())->method('sprint')->willReturn($this->sprint);
    $this->subscription->expects($this->exactly(2))->method('state')->willReturn($this->state);
    $this->subscription->expects($this->once())->method('currentStreamName')->willReturn('foo');
    $this->streamManager->expects($this->once())->method('bind')->with('foo', 5, 'some_event_time')->willReturn(true);

    $eventProcessor = new EventProcessor(function (DomainEvent $event, array $state): array {
        expect($event)
            ->toBe($this->event)
            ->and($state)->toBe($this->state->get());

        return $event->toContent();
    });

    $result = $eventProcessor($this->subscription, $this->event, 5);

    expect($result)
        ->toBe($inProgress)
        ->and($this->sprint->inProgress())->toBe($inProgress)
        ->and($this->state->get())->toBe(['name' => 'steph']);
};

test('process event and return result of sprint in progress', function (bool $inProgress) use ($assertEventProcessed): void {
    $this->option->expects($this->once())->method('getSignal')->willReturn(false);

    $inProgress ? $this->sprint->continue() : $this->sprint->stop();

    $assertEventProcessed->call($this, $inProgress);
})->with(['in progress' => [true], 'stopped' => [false]]);

test('process event and stop sprint on handle signal', function () use ($assertEventProcessed): void {
    $this->option->expects($this->once())->method('getSignal')->willReturn(true);

    $this->sprint->continue();

    pcntl_signal(SIGTERM, function () {
        $this->sprint->stop();
    });

    posix_kill(posix_getpid(), SIGTERM);

    $assertEventProcessed->call($this, false);
});

test('does not process event when gap has been detected and always return false', function () {
    $this->option->expects($this->once())->method('getSignal')->willReturn(false);

    $this->sprint->continue();

    $this->subscription->expects($this->never())->method('sprint');
    $this->subscription->expects($this->never())->method('state');
    $this->subscription->expects($this->once())->method('currentStreamName')->willReturn('foo');

    $this->streamManager->expects($this->once())->method('bind')->with('foo', 5, 'some_event_time')->willReturn(false);

    $eventProcessor = new EventProcessor(function (): void {
        throw new LogicException('test: should not be called');
    });

    $result = $eventProcessor($this->subscription, $this->event, 5);

    expect($result)->toBeFalse();
});
