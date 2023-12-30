<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Feature\Projection;

use Chronhub\Storm\Projector\Scope\EmitterAccess;
use Chronhub\Storm\Projector\Workflow\HaltOn;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Factory\InMemoryFactory;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Symfony\Component\Uid\Uuid;

beforeEach(function () {
    $this->testFactory = new InMemoryFactory();
    $this->eventStore = $this->testFactory->getEventStore();
    $this->projectorManager = $this->testFactory->getManager();
});

it('stop when gap detected', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);
    $fromIncludedPosition = $this->projectorManager->queryScope()->fromIncludedPosition();

    // induce gap
    $stream = $this->testFactory->getStream('user', 10, null, $eventId, SomeEvent::class, 12);
    $this->eventStore->amend($stream);

    expect($this->eventStore->hasStream(new StreamName('customer')))->toBeFalse();

    // run without halt on gap
    $keepEmitting = $this->projectorManager->newEmitterProjector('customer');

    $keepEmitting->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($fromIncludedPosition)
        ->when(function (EmitterAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ->incrementState()
                ->stopWhen($scope['count'] === 20);
        })
        ->run(true);

    expect($keepEmitting->getState())->toBe(['count' => 20]);

    // delete projection
    $keepEmitting->delete(false);

    // expect halt on gap
    $haltOnGap = $this->projectorManager->newEmitterProjector('customer');

    $haltOnGap
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($fromIncludedPosition)
        ->haltOn(fn (HaltOn $halt): HaltOn => $halt->gapDetected())
        ->when(function (EmitterAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })
        ->run(true);

    expect($haltOnGap->getState())->toBe(['count' => 10]);
});

it('stop when counter is reached', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 50, null, $eventId);
    $this->eventStore->firstCommit($stream);
    $fromIncludedPosition = $this->projectorManager->queryScope()->fromIncludedPosition();

    expect($this->eventStore->hasStream(new StreamName('customer')))->toBeFalse();

    // run without halt on gap
    $keepEmitting = $this->projectorManager->newEmitterProjector('customer');

    $keepEmitting->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($fromIncludedPosition)
        ->when(function (EmitterAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ->incrementState();
        })
        ->run(false);

    expect($keepEmitting->getState())->toBe(['count' => 50]);

    // delete projection
    $keepEmitting->delete(false);

    // stop at 22
    $haltOn = $this->projectorManager->newEmitterProjector('customer');

    $haltOn
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($fromIncludedPosition)
        ->haltOn(fn (HaltOn $halt): HaltOn => $halt->masterCounterLimit(22))
        ->when(function (EmitterAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })
        ->run(true);

    expect($haltOn->getState())->toBe(['count' => 22]);
});
