<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Workflow\EventReactor;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\Uses\TestingSubscriptionFactory;
use LogicException;
use TypeError;

use function pcntl_signal;
use function posix_getpid;
use function posix_kill;

uses(TestingSubscriptionFactory::class);

beforeEach(function () {
    $this->setUpWithSubscription(PersistentSubscriber::class, ProjectorScope::class);
    $this->event = SomeEvent::fromContent(['name' => 'steph'])->withHeader(Header::EVENT_TIME, 'some_event_time');
});

$assertEventProcessed = function (bool $inProgress) {
    $this->fakeInitializeUserState();
    $this->subscription->expects($this->once())->method('persistWhenCounterIsReached');
    $this->streamManager->expects($this->once())->method('bind')->with($this->currentStreamName, 5, 'some_event_time')->willReturn(true);

    $eventProcessor = new EventReactor(function (DomainEvent $event, array $state, ProjectorScope $scope): array {
        expect($event)
            ->toBe($this->event)
            ->and($state)->toBe($this->state->get())
            ->and($scope)->toBe($this->projectorScope);

        return ['altered content'];
    }, $this->projectorScope);

    $result = $eventProcessor($this->subscription, $this->event, 5);

    expect($result)
        ->toBe($inProgress)
        ->and($this->sprint->inProgress())->toBe($inProgress)
        ->and($this->state->get())->toBe(['altered content']);
};

test('process event and return sprint in progress', function (bool $inProgress) use ($assertEventProcessed): void {
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
    $this->streamManager->expects($this->once())->method('bind')->with($this->currentStreamName, 5, 'some_event_time')->willReturn(false);

    $eventProcessor = new EventReactor(function (): void {
        throw new LogicException('test: should not be called');
    }, $this->projectorScope);

    $result = $eventProcessor($this->subscription, $this->event, 5);

    expect($result)->toBeFalse();
});

test('raise error when event time extracted from event header is not string or date time', function () {
    $this->event = SomeEvent::fromContent(['name' => 'steph']);

    $this->subscription->expects($this->never())->method('sprint');
    $this->subscription->expects($this->never())->method('state');
    $this->streamManager->expects($this->never())->method('bind');

    $eventProcessor = new EventReactor(function (): void {
        throw new LogicException('test: should not be called');
    }, $this->projectorScope);

    $eventProcessor($this->subscription, $this->event, 5);
})->throws(TypeError::class, 'Argument #3 ($eventTime) must be of type DateTimeImmutable|string|false');
