<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Feature\Projection;

use Chronhub\Storm\Contracts\Projector\EmitterScope;
use Chronhub\Storm\Tests\Factory\InMemoryFactory;
use Chronhub\Storm\Tests\Stubs\Double\AnotherEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Symfony\Component\Uid\Uuid;

beforeEach(function () {
    $this->testFactory = new InMemoryFactory();
    $this->eventStore = $this->testFactory->getEventStore();
    $this->projectorManager = $this->testFactory->getManager();
});

it('can run emitter projection 111', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10000, null, $eventId);
    $this->eventStore->firstCommit($stream);

    $eventId1 = Uuid::v4()->toRfc4122();
    $stream1 = $this->testFactory->getStream('foo', 5000, '+10 seconds', $eventId1);
    $this->eventStore->firstCommit($stream1);

    // create a projection
    $projector = $this->projectorManager->newEmitterProjector('customer');

    // run projection
    $projector
        ->initialize(fn () => ['count' => ['user' => 0, 'foo' => 0, 'total' => 0]])
        ->subscribeToStream('user', 'foo')
        //->until(10)
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (EmitterScope $scope): void {
            $scope
                ->ackOneOf(SomeEvent::class, AnotherEvent::class)
                ->incrementState('count.'.$scope->streamName())
                ->incrementState('count.total')
                ->stopWhen($scope['count']['total'] === 15000);
        })->run(true);

    expect($projector->getState())->toBe(['count' => ['user' => 10000, 'foo' => 5000, 'total' => 15000]]);
});
