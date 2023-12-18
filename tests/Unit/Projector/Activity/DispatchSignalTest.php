<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Activity;

use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\StateManagement;
use Chronhub\Storm\Projector\Workflow\Activity\DispatchSignal;

use function pcntl_signal;
use function posix_getpid;
use function posix_kill;

beforeEach(function () {
    $this->activity = new DispatchSignal();
    $this->subscription = $this->createMock(StateManagement::class);
    $this->option = $this->createMock(ProjectionOption::class);
    $this->subscription->expects($this->once())->method('option')->willReturn($this->option);
    $this->next = function (StateManagement $subscription) {
        return fn () => $subscription;
    };
});

it('dispatch signal when projection option signal is active', function () {
    $this->option->expects($this->once())->method('getSignal')->willReturn(true);

    $called = false;
    pcntl_signal(SIGINT, function () use (&$called) {
        $called = true;
    });

    posix_kill(posix_getpid(), SIGINT);

    $result = ($this->activity)($this->subscription, $this->next);

    expect($called)
        ->toBe(true)
        ->and($result())->toBe($this->subscription);
});

it('does not dispatch signal when projection option signal is inactive', function () {
    $this->option->expects($this->once())->method('getSignal')->willReturn(false);

    $called = false;
    pcntl_signal(SIGINT, function () use (&$called) {
        $called = true;
    });

    posix_kill(posix_getpid(), SIGINT);

    $result = ($this->activity)($this->subscription, $this->next);

    expect($called)
        ->toBe(false)
        ->and($result())->toBe($this->subscription);
})->skip('failed with pest cli');
