<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Factory\MergeStreamIteratorFactory;

function getInstance(bool $shouldHandled, bool $inProgress, int $stopAt = null, int $failedAt = null): object
{
    return new class($shouldHandled, $inProgress, $stopAt, $failedAt)
    {
        private int $count = 0;

        private int $failed = 0;

        public function __construct(
            private readonly bool $shouldHandled,
            private readonly bool $inProgress,
            private readonly ?int $stopAt,
            private readonly ?int $failedAt
        ) {
        }

        public function __invoke(Subscription $subscription, DomainEvent $event, $position): bool
        {
            if (! $this->shouldHandled) {
                return false;
            }

            if ($this->failedAt !== null) {
                $this->failed++;

                if ($this->failed === $this->failedAt) {
                    return false;
                }
            }

            if ($this->stopAt !== null) {
                $this->count++;

                if ($this->count === $this->stopAt) {
                    return false;
                }
            }

            return $this->inProgress;
        }
    };
}

beforeEach(function (): void {
    $this->sprint = new Sprint();
    $this->subscription = $this->createMock(Subscription::class);
    $this->iterator = MergeStreamIteratorFactory::getIterator();
    $this->next = fn () => fn () => 42;
});

it('iterate over streams till events are successfully handled and in progress', function () {
    $activity = new HandleStreamEvent(getInstance(true, true));

    $numberOfEvents = $this->iterator->count();

    $this->subscription->expects($this->once())->method('pullStreamIterator')->willReturn($this->iterator);
    $this->subscription->expects($this->exactly($numberOfEvents))->method('sprint')->willReturn($this->sprint);
    $this->subscription->expects($this->exactly($numberOfEvents))->method('setStreamName');

    $this->sprint->continue();

    $next = $activity($this->subscription, $this->next);

    expect($next())->toBe(42);
});

it('iterate over streams till events are successfully handled and will stop from in progress', function () {
    $activity = new HandleStreamEvent(getInstance(true, true, 3));

    $this->subscription->expects($this->once())->method('pullStreamIterator')->willReturn($this->iterator);
    $this->subscription->expects($this->exactly(2))->method('sprint')->willReturn($this->sprint);
    $this->subscription->expects($this->exactly(3))->method('setStreamName');

    $this->sprint->continue();

    $next = $activity($this->subscription, $this->next);

    expect($next())->toBe(42);
});

it('iterate till in progress and an event failed to be handled', function () {
    $activity = new HandleStreamEvent(getInstance(true, true, null, 5));

    $this->subscription->expects($this->once())->method('pullStreamIterator')->willReturn($this->iterator);
    $this->subscription->expects($this->exactly(4))->method('sprint')->willReturn($this->sprint);
    $this->subscription->expects($this->exactly(5))->method('setStreamName');

    $this->sprint->continue();

    $next = $activity($this->subscription, $this->next);

    expect($next())->toBe(42);
});

it('iterate once successfully when sprint is stopped', function () {
    $activity = new HandleStreamEvent(getInstance(true, false));

    $this->subscription->expects($this->once())->method('pullStreamIterator')->willReturn($this->iterator);
    $this->subscription->expects($this->never())->method('sprint')->willReturn($this->sprint);
    $this->subscription->expects($this->exactly(1))->method('setStreamName');

    $this->sprint->continue();

    $next = $activity($this->subscription, $this->next);

    expect($next())->toBe(42);
});
