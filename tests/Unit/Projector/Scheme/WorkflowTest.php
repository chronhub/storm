<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Closure;

beforeEach(function (): void {
    $this->sprint = new Sprint();
    $this->subscription = $this->createMock(Subscription::class);
    $this->subscription->expects($this->any())->method('sprint')->willReturn($this->sprint);
});

function getActivities(&$called): array
{
    return [
        function (Subscription $subscription, Closure $next) use (&$called): Closure|bool {
            $called++;

            if (! $subscription->sprint()->inProgress()) {
                return false;
            }

            return $next($subscription);
        },
        function (Subscription $subscription, Closure $next) use (&$called): Closure|bool {
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
        static fn (Subscription $subscription): bool => $subscription->sprint()->inProgress()
    );

    expect($inProgress)
        ->toBeFalse()
        ->and($called)->toBe(2);
});

it('process workflow can be stopped', function (): void {
    $this->sprint->stop();

    $called = 0;

    $activities = getActivities($called);

    $workflow = new Workflow($this->subscription, $activities);

    $inProgress = $workflow->process(
        static fn (Subscription $subscription): bool => $subscription->sprint()->inProgress()
    );

    expect($inProgress)
        ->toBeFalse()
        ->and($called)->toBe(1);
});
