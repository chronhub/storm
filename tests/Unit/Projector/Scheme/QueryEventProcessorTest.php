<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\StateManagement;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\Uses\TestingSubscriptionFactory;

use function pcntl_signal;
use function posix_getpid;
use function posix_kill;

uses(TestingSubscriptionFactory::class);

beforeEach(function () {
    $this->setUpWithSubscription(StateManagement::class, ProjectorScope::class);
    $this->event = SomeEvent::fromContent(['name' => 'steph'])->withHeader(Header::EVENT_TIME, 'not used');
});

$assertEventProcessed = function (bool $inProgress) {
    $this->fakeInitializeUserState();
    $this->streamManager->expects($this->once())->method('bind')->with($this->currentStreamName, 5, false)->willReturn(true);

    $eventProcessor = new EventProcessor(function (DomainEvent $event, array $state, ProjectorScope $scope): array {
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

test('always process event and return sprint in progress', function (bool $inProgress) use ($assertEventProcessed): void {
    $this->option->expects($this->once())->method('getSignal')->willReturn($inProgress);

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
