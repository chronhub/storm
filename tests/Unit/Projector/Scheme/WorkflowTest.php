<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\StateManagement;
use Chronhub\Storm\Projector\Workflow\Sprint;
use Chronhub\Storm\Projector\Workflow\Workflow;
use Closure;

beforeEach(function (): void {
    $this->sprint = new Sprint();
    $this->subscription = $this->createMock(StateManagement::class);
    $this->subscription->expects($this->any())->method('sprint')->willReturn($this->sprint);
});

function getActivities(&$called): array
{
    return [
        function (StateManagement $subscription, Closure $next) use (&$called): Closure|bool {
            $called++;

            if (! $subscription->sprint()->inProgress()) {
                return false;
            }

            return $next($subscription);
        },
        function (StateManagement $subscription, Closure $next) use (&$called): Closure|bool {
            $called++;

            $subscription->sprint()->stop();

            return $next($subscription);
        },
    ];
}

it('process workflow', function (): void {
    $this->sprint->continue();

    $called = 0;

    $activities = getActivities($called);

    $workflow = new Workflow($this->subscription, $activities);

    $inProgress = $workflow->process(
        static fn (StateManagement $subscription): bool => $subscription->sprint()->inProgress()
    );

    expect($inProgress)
        ->toBeFalse()
        ->and($called)->toBe(2);
});

it('process workflow can be stopped', function (): void {
    $this->sprint->halt();

    $called = 0;

    $activities = getActivities($called);

    $workflow = new Workflow($this->subscription, $activities);

    $inProgress = $workflow->process(
        static fn (StateManagement $subscription): bool => $subscription->sprint()->inProgress()
    );

    expect($inProgress)
        ->toBeFalse()
        ->and($called)->toBe(1);
});
