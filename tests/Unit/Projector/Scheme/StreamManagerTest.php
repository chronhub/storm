<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Scheme\EventStreamLoader;
use Chronhub\Storm\Projector\Scheme\StreamManager;

beforeEach(function (): void {
    $this->clock = $this->createMock(SystemClock::class);
    $this->loader = $this->createMock(EventStreamLoader::class);
});

test('new instance', function () {
    $this->streamManager = new StreamManager($this->loader, $this->clock, [1], null);

    expect($this->streamManager->retriesInMs)->toBe([1])
        ->and($this->streamManager->detectionWindows)->toBeNull()
        ->and($this->streamManager->hasGap())->toBeFalse()
        ->and($this->streamManager->retries())->toBe(0)
        ->and($this->streamManager->getGaps())->toBeEmpty()
        ->and($this->streamManager->all())->toBeEmpty()
        ->and($this->streamManager->jsonSerialize())->toEqual(['gaps' => [], 'positions' => []]);
});
