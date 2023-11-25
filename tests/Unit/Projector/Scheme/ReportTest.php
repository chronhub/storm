<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\Profiler;
use RuntimeException;

beforeEach(function () {
    $this->profiler = new Profiler();
    $this->subscription = $this->createMock(Subscription::class);
});

it('start cycle', function () {
    $this->subscription->expects($this->once())->method('currentStatus')->willReturn(ProjectionStatus::RUNNING);
    $this->profiler->start($this->subscription);

    $cycles = $this->profiler->getData();
    expect($cycles)->toHaveCount(1);

    $cycle = $cycles[1];

    expect($cycle)->toHaveKey('start')
        ->and($cycle)->not()->toHaveKey('end')
        ->and($cycle)->toHaveKey('error')->and($cycle['error'])->toBeNull()
        ->and($cycle['start']['status'])->toBe(ProjectionStatus::RUNNING)
        ->and($cycle)->and($cycle['start']['memory'])->toBeGreaterThan(0)
        ->and($cycle)->and($cycle['start']['at'])->toBeFloat();
});

it('end cycle', function () {
    $runningStatus = ProjectionStatus::RUNNING;
    $stoppedStatus = ProjectionStatus::STOPPING;

    $this->subscription->expects($this->exactly(2))->method('currentStatus')
        ->willReturnOnConsecutiveCalls($runningStatus, $stoppedStatus);

    $this->profiler->start($this->subscription);
    expect($this->profiler->getData()[1]['start']['status'])->toBe($runningStatus);

    $startedCycle = $this->profiler->getData()[1];

    $this->profiler->end($this->subscription);

    $cycle = $this->profiler->getData()[1];
    expect($cycle)->toHaveKey('start')->and($cycle['start'])->toBe($startedCycle['start'])
        ->and($cycle)->toHaveKey('end')->and($cycle['end'])->toHaveKey('status')->and($cycle['end']['status'])->toBe($stoppedStatus)
        ->and($cycle)->toHaveKey('end')->and($cycle['end'])->toHaveKey('memory')->and($cycle['end']['memory'])->toBeGreaterThan(0)
        ->and($cycle)->toHaveKey('end')->and($cycle['end'])->toHaveKey('at')->and($cycle['end']['at'])->toBeFloat()
        ->and($cycle)->toHaveKey('error')->and($cycle['error'])->toBeNull();
});

it('report exception raised from subscription', function () {
    $exception = new RuntimeException('error');

    $this->subscription->expects($this->exactly(2))->method('currentStatus')->willReturn(ProjectionStatus::RUNNING);

    $this->profiler->start($this->subscription);
    $this->profiler->end($this->subscription, $exception);

    $cycle = $this->profiler->getData()[1];
    expect($cycle)->toHaveKey('error')
        ->and($cycle['error'])->toHaveKey('class')->and($cycle['error']['class'])->toBe(RuntimeException::class)
        ->and($cycle['error'])->toHaveKey('message')->and($cycle['error']['message'])->toBe('error');
});

it('handle multiple cycles', function () {
    $expectedCycles = 3;
    $runningStatus = ProjectionStatus::RUNNING;
    $this->subscription->expects($this->exactly($expectedCycles * 2))->method('currentStatus')->willReturn($runningStatus);

    while ($expectedCycles > 0) {
        $this->profiler->start($this->subscription);
        $this->profiler->end($this->subscription);
        $expectedCycles--;
    }

    expect($this->profiler->getData())->toHaveCount(3);
});
