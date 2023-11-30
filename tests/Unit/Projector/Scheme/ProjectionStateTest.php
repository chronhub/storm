<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Projector\Scheme\ProjectionState;

beforeEach(function () {
    $this->state = new ProjectionState();
});

it('can be instantiated', function () {
    expect($this->state->get())->toBe([]);
});

it('can set state', function (array $state) {
    $this->state->put($state);

    expect($this->state->get())->toBe($state);
})->with([
    'empty' => [[]],
    'not empty' => [['foo' => 'bar']],
]);

it('can override state', function () {
    $this->state->put(['foo' => 'bar']);

    expect($this->state->get())->toBe(['foo' => 'bar']);

    $this->state->put(['baz' => 'foo_bar']);

    expect($this->state->get())->toBe(['baz' => 'foo_bar']);
});

it('can set empty state', function () {
    $this->state->put(['foo' => 'bar']);

    expect($this->state->get())->toBe(['foo' => 'bar']);

    $this->state->put([]);

    expect($this->state->get())->toBe([]);
});

it('can reset state', function () {
    $this->state->put(['foo' => 'bar']);

    expect($this->state->get())->toBe(['foo' => 'bar']);

    $this->state->reset();

    expect($this->state->get())->toBe([]);
});
