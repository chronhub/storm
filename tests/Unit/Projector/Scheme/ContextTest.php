<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Workflow\DefaultContext;
use Closure;
use DateInterval;

beforeEach(function () {
    $this->context = new DefaultContext();
});

test('can initialize context once', function () {
    $this->context->initialize(fn (): array => ['count' => 0]);

    expect($this->context->userState())->toBeInstanceOf(Closure::class)
        ->and($this->context->userState()())->toEqual(['count' => 0]);

    $this->context->initialize(fn (): array => ['count' => 25]);
})->throws(InvalidArgumentException::class, 'Projection already initialized');

test('can set query filter once', function () {
    $queryFilter = $this->createMock(QueryFilter::class);

    $this->context->withQueryFilter($queryFilter);

    expect($this->context->queryFilter())->toBe($queryFilter);

    $this->context->withQueryFilter($queryFilter);
})->throws(InvalidArgumentException::class, 'Projection query filter already set');

test('can set timer once', function (DateInterval|string|int $interval) {
    $this->context->until($interval);

    expect($this->context->timer())->toBeInstanceOf(DateInterval::class)
        ->and($this->context->timer()->s)->toBe(5);

    $this->context->until($interval);
})
    ->with([5, 'PT5S', 'pt5s', new DateInterval('PT5S')])
    ->throws(InvalidArgumentException::class, 'Projection timer already set');

test('can set reactors once', function () {
    $reactors = fn () => null;

    $this->context->when($reactors);

    expect($this->context->reactors())->toBe($reactors);

    $this->context->when($reactors);
})->throws(InvalidArgumentException::class, 'Projection reactors already set');

test('from streams', function () {
    $this->context->subscribeToStream('foo', 'bar');

    expect($this->context->queries())->toBe(['names' => ['foo', 'bar']]);
});

test('from categories', function () {
    $this->context->subscribeToCategory('foo', 'bar');

    expect($this->context->queries())->toBe(['categories' => ['foo', 'bar']]);
});

test('from all', function () {
    $this->context->subscribeToAll();

    expect($this->context->queries())->toBe(['all' => true]);
});

test('can not set queries twice from streams', function () {
    $this->context->subscribeToStream('foo');
    $this->context->subscribeToStream('bar');
})->throws(InvalidArgumentException::class, 'Projection streams all|names|categories already set');

test('can not set queries twice from categories', function () {
    $this->context->subscribeToCategory('foo');
    $this->context->subscribeToStream('bar');
})->throws(InvalidArgumentException::class, 'Projection streams all|names|categories already set');

test('can not set queries twice from all', function () {
    $this->context->subscribeToAll();
    $this->context->subscribeToStream('bar');
})->throws(InvalidArgumentException::class, 'Projection streams all|names|categories already set');
