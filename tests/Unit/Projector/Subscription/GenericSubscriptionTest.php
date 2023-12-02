<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Subscription\GenericSubscription;
use Chronhub\Storm\Tests\Uses\TestingGenericSubscription;
use Error;

uses(TestingGenericSubscription::class);

beforeEach(function (): void {
    $this->setUpGenericSubscription();
});

it('test default instance', function (): void {
    expect($this->subscription)->toBeInstanceOf(GenericSubscription::class)
        ->and($this->subscription)->toBeInstanceOf(Subscription::class)
        ->and($this->subscription->option())->toBe($this->option)
        ->and($this->subscription->streamManager())->toBe($this->streamManager)
        ->and($this->subscription->clock())->toBe($this->clock)
        ->and($this->subscription->chronicler())->toBe($this->chronicler)
        ->and($this->subscription->state()->get())->toBeEmpty()
        ->and($this->subscription->sprint()->inProgress())->toBeFalse()
        ->and($this->subscription->sprint()->inBackground())->toBeFalse()
        ->and($this->subscription->currentStreamName())->toBeNull()
        ->and($this->subscription->currentStatus())->toBe(ProjectionStatus::IDLE);
});

it('set and get current stream name by reference', function () {
    // todo remove fn from scope
    $streamName = 'customer-123';

    $this->subscription->setStreamName($streamName);

    $fn = function (): string {
        return $this->subscription->currentStreamName();
    };

    expect($fn())->toBe('customer-123');

    $streamName = 'customer-456';

    expect($fn())->toBe('customer-456');
});

it('set current stream name by reference 2', function () {
    $streamName = 'customer-123';

    $this->subscription->setStreamName($streamName);

    expect($this->subscription->currentStreamName())->toBe('customer-123');

    $streamName = 'customer-456';

    expect($this->subscription->currentStreamName())->toBe('customer-456');
});

it('set and get status', function (ProjectionStatus $expectedStatus) {
    $idle = ProjectionStatus::IDLE;

    $this->subscription->setStatus($idle);

    expect($this->subscription->currentStatus())->toBe($idle);

    $this->subscription->setStatus($expectedStatus);

    expect($this->subscription->currentStatus())->toBe($expectedStatus);
})->with('projection status');

it('fails get context when not composed and raise error exception', function (): void {
    $nope = $this->subscription->context();
})->throws(Error::class, 'Typed property Chronhub\Storm\Projector\Subscription\GenericSubscription::$context must not be accessed before initialization');
