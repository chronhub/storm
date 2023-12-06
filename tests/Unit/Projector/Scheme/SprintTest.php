<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Projector\Scheme\Sprint;

beforeEach(function (): void {
    $this->sprint = new Sprint();
});

test('new instance', function (): void {
    expect($this->sprint->inBackground())->toBeFalse()
        ->and($this->sprint->inProgress())->toBeFalse();
});

test('start sprint', function (): void {
    expect($this->sprint->inProgress())->toBeFalse();

    $this->sprint->continue();

    expect($this->sprint->inProgress())->toBeTrue();
});

test('run in background', function (): void {
    $this->sprint->runInBackground(true);

    expect($this->sprint->inBackground())->toBeTrue();

    $this->sprint->runInBackground(false);

    expect($this->sprint->inBackground())->toBeFalse();
});

test('stop sprint', function (): void {
    $this->sprint->continue();

    expect($this->sprint->inProgress())->toBeTrue();

    $this->sprint->stop();

    expect($this->sprint->inProgress())->toBeFalse();
});
