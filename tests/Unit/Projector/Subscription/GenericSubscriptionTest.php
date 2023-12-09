<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\ChroniclerDecorator;
use Chronhub\Storm\Contracts\Projector\StateManagement;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Subscription\Beacon;
use Chronhub\Storm\Tests\Factory\MergeStreamIteratorFactory;
use Chronhub\Storm\Tests\Uses\TestingGenericSubscription;

uses(TestingGenericSubscription::class);

beforeEach(function (): void {
    $this->setUpGenericSubscription();
});

it('test default instance', function (): void {
    expect($this->subscription)->toBeInstanceOf(Beacon::class)
        ->and($this->subscription)->toBeInstanceOf(StateManagement::class)
        ->and($this->subscription->option())->toBe($this->option)
        ->and($this->subscription->streamManager())->toBe($this->streamManager)
        ->and($this->subscription->clock())->toBe($this->clock)
        ->and($this->subscription->chronicler())->toBe($this->chronicler)
        ->and($this->subscription->context())->toBe($this->context)
        ->and($this->subscription->state()->get())->toBeEmpty()
        ->and($this->subscription->sprint()->inProgress())->toBeFalse()
        ->and($this->subscription->sprint()->inBackground())->toBeFalse()
        ->and($this->subscription->currentStreamName())->toBeNull()
        ->and($this->subscription->currentStatus())->toBe(ProjectionStatus::IDLE);
});

it('retrieve innermost chronicler in constructor', function (): void {
    $this->setUpAndAndAssertInnerMostChronicler();

    expect($this->subscription->chronicler())->not()->toBeInstanceOf(ChroniclerDecorator::class);
});

it('set current stream name by reference', function () {
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

it('can set and get state', function () {
    expect($this->subscription->state()->get())->toBeEmpty();

    $this->subscription->state()->put(['foo' => 'bar']);

    expect($this->subscription->state()->get())->toBe(['foo' => 'bar']);
});

it('can initialize again state to his original state', function () {
    expect($this->subscription->state()->get())->toBeEmpty();

    $this->context->expects($this->once())->method('userState')->willReturn(fn (): array => ['count' => 1]);

    $this->subscription->state()->put(['foo' => 'bar']);

    expect($this->subscription->state()->get())->toBe(['foo' => 'bar']);

    $this->subscription->initializeAgain();

    expect($this->subscription->state()->get())->toBe(['count' => 1]);
});

it('can start subscription', function (bool $inBackground) {
    expect($this->subscription->sprint()->inProgress())->toBeFalse()
        ->and($this->subscription->sprint()->inBackground())->toBeFalse()
        ->and($this->subscription->state()->get())->toBeEmpty();

    $this->subscription->sprint()->runInBackground($inBackground);

    $this->context->expects($this->once())->method('userState')->willReturn(fn (): array => ['count' => 1]);

    $this->subscription->start($inBackground);

    expect($this->subscription->state()->get())->toBe(['count' => 1])
        ->and($this->subscription->sprint()->inProgress())->toBeTrue()
        ->and($this->subscription->sprint()->inBackground())->toBe($inBackground);
})->with(['run in background' => [true], 'run once' => [false]]);

it('can set and pull loaded streams', function () {
    $streamIterator = MergeStreamIteratorFactory::getIterator();

    $this->subscription->setStreamIterator($streamIterator);

    expect($this->subscription->pullStreamIterator())->toBe($streamIterator)
        ->and($this->subscription->pullStreamIterator())->toBeNull();
});
