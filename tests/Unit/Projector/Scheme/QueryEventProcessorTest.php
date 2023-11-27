<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Options\InMemoryProjectionOption;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Scheme\StreamManager;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use LogicException;

beforeEach(function () {
    $this->subscription = $this->createMock(Subscription::class);
    $this->streamManager = $this->createMock(StreamManager::class);
    $this->event = SomeEvent::fromContent(['name' => 'steph']);
    $this->state = new ProjectionState();
    $this->option = new InMemoryProjectionOption();
    $this->sprint = new Sprint();
});

test('process event', function (bool $inProgress) {
    $inProgress ? $this->sprint->continue() : $this->sprint->stop();

    $this->subscription->expects($this->once())->method('option')->willReturn($this->option);
    $this->subscription->expects($this->once())->method('sprint')->willReturn($this->sprint);
    $this->subscription->expects($this->exactly(2))->method('state')->willReturn($this->state);
    $this->subscription->expects($this->once())->method('currentStreamName')->willReturn('foo');
    $this->subscription->expects($this->once())->method('streamManager')->willReturn($this->streamManager);

    $this->streamManager->expects($this->once())->method('bind')
        ->with('foo', 5, false)
        ->willReturn(true);

    $eventProcessor = new EventProcessor(function (DomainEvent $event, array $state): array {
        expect($event)->toBe($this->event)
            ->and($state)->toBe($this->state->get());

        return $event->toContent();
    });

    $result = $eventProcessor($this->subscription, $this->event, 5);

    expect($result)->toBe($this->sprint->inProgress())
        ->and($this->state->get())->toBe(['name' => 'steph']);
})->with([
    'sprint in progress' => [true],
    'sprint stopped' => [false],
]);

test('does not process event when gap has been detected', function () {
    $this->subscription->expects($this->never())->method('option');
    $this->subscription->expects($this->never())->method('sprint');
    $this->subscription->expects($this->never())->method('state');
    $this->subscription->expects($this->once())->method('currentStreamName')->willReturn('foo');
    $this->subscription->expects($this->once())->method('streamManager')->willReturn($this->streamManager);

    $this->streamManager->expects($this->once())->method('bind')
        ->with('foo', 5, false)
        ->willReturn(false);

    $eventProcessor = new EventProcessor(function (): void {
        throw new LogicException('should not be called');
    });

    $result = $eventProcessor($this->subscription, $this->event, 5);

    expect($result)->toBeFalse();
});

test('dispatch signal', function () {

})->todo();
