<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Feature\Projection;

use Chronhub\Storm\Contracts\Projector\EmitterScope;
use Chronhub\Storm\Projector\Scope\EmitterAccess;
use Chronhub\Storm\Projector\Workflow\HaltOn;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Factory\InMemoryFactory;
use Chronhub\Storm\Tests\Stubs\Double\AnotherEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Symfony\Component\Uid\Uuid;

use function time;

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

    // run without halting on gap
    $keepEmitting = $this->projectorManager->newEmitterProjector('customer');

    $keepEmitting->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->filter($fromIncludedPosition)
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
        ->filter($fromIncludedPosition)
        ->haltOn(function (HaltOn $halt): HaltOn {
            return $halt->gapDetected();
        })
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
        ->filter($fromIncludedPosition)
        ->when(function (EmitterAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
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
        ->filter($fromIncludedPosition)
        ->haltOn(fn (HaltOn $halt): HaltOn => $halt->streamEventLimitReach(22))
        ->when(function (EmitterAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })
        ->run(true);

    expect($haltOn->getState())->toBe(['count' => 22]);
});

it('stop when time expires', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 5, null, $eventId);
    $this->eventStore->firstCommit($stream);
    $fromIncludedPosition = $this->projectorManager->queryScope()->fromIncludedPosition();

    expect($this->eventStore->hasStream(new StreamName('customer')))->toBeFalse();

    // run for 5 seconds
    $haltOn = $this->projectorManager->newEmitterProjector('customer');

    $start = time();
    $expiredAt = $start + 2;

    $haltOn
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->filter($fromIncludedPosition)
        ->haltOn(fn (HaltOn $halt): HaltOn => $halt->timeExpired($expiredAt))
        ->when(function (EmitterAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })
        ->run(true);

    expect($haltOn->getState())->toBe(['count' => 5]);
});

it('stop after first cycle when expired time is zero', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 50, null, $eventId);
    $this->eventStore->firstCommit($stream);
    $fromIncludedPosition = $this->projectorManager->queryScope()->fromIncludedPosition();

    expect($this->eventStore->hasStream(new StreamName('customer')))->toBeFalse();

    $haltOn = $this->projectorManager->newEmitterProjector('customer');

    $haltOn
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->filter($fromIncludedPosition)
        ->haltOn(fn (HaltOn $halt): HaltOn => $halt->timeExpired(0))
        ->when(function (EmitterAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })
        ->run(true);

    expect($haltOn->getState())->toBe(['count' => 50]);
});

it('stop at cycle', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 50, null, $eventId);
    $this->eventStore->firstCommit($stream);
    $fromIncludedPosition = $this->projectorManager->queryScope()->fromIncludedPosition();

    expect($this->eventStore->hasStream(new StreamName('customer')))->toBeFalse();

    // stop at cycle 2
    $haltOn = $this->projectorManager->newEmitterProjector('customer');

    $haltOn
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->filter($fromIncludedPosition)
        ->haltOn(fn (HaltOn $halt): HaltOn => $halt->cycleReach(2))
        ->when(function (EmitterAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })
        ->run(true);

    expect($haltOn->getState())->toBe(['count' => 50]);
});

it('can run emitter again and reset main limit', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10000, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newEmitterProjector('customer');

    // run projection
    $projector
        ->initialize(fn () => ['count' => ['user' => 0, 'foo' => 0, 'total' => 0]])
        ->subscribeToStream('user', 'foo')
        ->haltOn(fn (HaltOn $haltOn): HaltOn => $haltOn->streamEventLimitReach(10000, true))
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (EmitterScope $scope): void {
            $scope
                ->ackOneOf(SomeEvent::class, AnotherEvent::class)
                ->incrementState('count.'.$scope->streamName())
                ->incrementState('count.total');
        })->run(true);

    expect($projector->getState())->toBe(['count' => ['user' => 10000, 'foo' => 0, 'total' => 10000]]);

    $eventId1 = Uuid::v4()->toRfc4122();
    $stream1 = $this->testFactory->getStream('foo', 5000, '+10 seconds', $eventId1);
    $this->eventStore->firstCommit($stream1);

    $projector
        ->haltOn(fn (HaltOn $haltOn): HaltOn => $haltOn->streamEventLimitReach(5000))
        ->run(true);

    expect($projector->getState())->toBe(['count' => ['user' => 10000, 'foo' => 5000, 'total' => 15000]]);
});

it('can run emitter again and do not reset main limit', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10000, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newEmitterProjector('customer');

    // run projection
    $projector
        ->initialize(fn () => ['count' => ['user' => 0, 'foo' => 0, 'total' => 0]])
        ->subscribeToStream('user', 'foo')
        ->haltOn(fn (HaltOn $haltOn): HaltOn => $haltOn->streamEventLimitReach(10000, false))
        ->filter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (EmitterScope $scope): void {
            $scope
                ->ackOneOf(SomeEvent::class, AnotherEvent::class)
                ->incrementState('count.'.$scope->streamName())
                ->incrementState('count.total');
        })->run(true);

    expect($projector->getState())->toBe(['count' => ['user' => 10000, 'foo' => 0, 'total' => 10000]]);

    $eventId1 = Uuid::v4()->toRfc4122();
    $stream1 = $this->testFactory->getStream('foo', 5000, '+10 seconds', $eventId1);
    $this->eventStore->firstCommit($stream1);

    $projector
        ->haltOn(fn (HaltOn $haltOn): HaltOn => $haltOn->streamEventLimitReach(15000))
        ->run(true);

    expect($projector->getState())->toBe(['count' => ['user' => 10000, 'foo' => 5000, 'total' => 15000]]);
});
