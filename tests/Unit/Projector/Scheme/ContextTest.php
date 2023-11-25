<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Closure;
use DateInterval;
use ReflectionFunction;

beforeEach(function () {
    $this->context = new Context();
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

    expect($this->context->reactors())->toBeInstanceOf(EventProcessor::class);

    $this->context->when($reactors);
})->throws(InvalidArgumentException::class, 'Projection reactors already set');

test('from streams', function () {
    $this->context->fromStreams('foo', 'bar');

    expect($this->context->queries())->toBe(['names' => ['foo', 'bar']]);
});

test('from categories', function () {
    $this->context->fromCategories('foo', 'bar');

    expect($this->context->queries())->toBe(['categories' => ['foo', 'bar']]);
});

test('from all', function () {
    $this->context->fromAll();

    expect($this->context->queries())->toBe(['all' => true]);
});

test('can not set queries twice from streams', function () {
    $this->context->fromStreams('foo');
    $this->context->fromStreams('foo');
})->throws(InvalidArgumentException::class, 'Projection streams all|names|categories already set');

test('can not set queries twice from categories', function () {
    $this->context->fromCategories('foo');
    $this->context->fromStreams('foo');
})->throws(InvalidArgumentException::class, 'Projection streams all|names|categories already set');

test('can not set queries twice from all', function () {
    $this->context->fromAll();
    $this->context->fromStreams('foo');
})->throws(InvalidArgumentException::class, 'Projection streams all|names|categories already set');

test('can bind user state', function () {
    $scope = $this->createMock(ProjectorScope::class);

    $this->context->initialize(fn () => ['count' => 0]);

    expect($this->context->bindUserState($scope))->toBe(['count' => 0]);

    $boundState = $this->context->userState();

    $ref = new ReflectionFunction($boundState);

    expect($ref->getClosureThis())->toBe($scope);
});

test('can bind reactors', function () {
    // hard to unit test
})->todo();
