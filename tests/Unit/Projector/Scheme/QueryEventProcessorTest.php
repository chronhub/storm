<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;

use function pcntl_signal;
use function posix_getpid;
use function posix_kill;

beforeEach(function () {
    $this->subscription = $this->createMock(Subscription::class);
    $this->streamManager = $this->createMock(StreamManagerInterface::class);
    $this->option = $this->createMock(ProjectionOption::class);
    $this->context = $this->createMock(ContextReaderInterface::class);

    $this->subscription->expects($this->any())->method('streamManager')->willReturn($this->streamManager);
    $this->state = new ProjectionState();
    $this->sprint = new Sprint();
    $this->event = SomeEvent::fromContent(['name' => 'steph']);
});

dataset('sprint in', ['in progress' => true, 'stopped' => false]);

$assertEventProcessed = function (bool $inProgress) {
    $this->context->expects($this->exactly(1))->method('userState')->willReturn(fn () => []);
    $this->subscription->expects($this->any())->method('context')->willReturn($this->context);
    $this->subscription->expects($this->once())->method('option')->willReturn($this->option);
    $this->subscription->expects($this->once())->method('sprint')->willReturn($this->sprint);
    $this->subscription->expects($this->exactly(2))->method('state')->willReturn($this->state);
    $this->subscription->expects($this->once())->method('currentStreamName')->willReturn('foo');
    $this->streamManager->expects($this->once())->method('bind')->with('foo', 5, false)->willReturn(true);

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

test('it always process event and return result of sprint in progress', function (bool $inProgress) use ($assertEventProcessed): void {
    $this->option->expects($this->once())->method('getSignal')->willReturn($inProgress);

    $inProgress ? $this->sprint->continue() : $this->sprint->stop();

    $assertEventProcessed->bindTo($this)($inProgress);
})->with(['in progress' => [true], 'stopped' => [false]]);

test('process event and stop sprint on handle signal', function () use ($assertEventProcessed): void {
    $this->option->expects($this->once())->method('getSignal')->willReturn(true);

    $this->sprint->continue();

    pcntl_signal(SIGTERM, function () {
        $this->sprint->stop();
    });

    posix_kill(posix_getpid(), SIGTERM);

    $assertEventProcessed->bindTo($this)(false);
});
