<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\ReadModel;

use Chronhub\Storm\Projector\ReadModel\InMemoryReadModel;

beforeEach(function (): void {
    $this->readModel = new InMemoryReadModel();
});

test('test instance', function (): void {
    expect($this->readModel->getContainer())->toBeEmpty();
});

test('test initialized', function (): void {
    expect($this->readModel->isInitialized())->toBeFalse();

    $this->readModel->initialize();

    expect($this->readModel->isInitialized())->toBeTrue();
});

test('test insert', function (): void {
    $this->readModel->stack('insert', 'foo', ['count' => 1]);

    $this->readModel->persist();

    expect($this->readModel->getContainer())->toEqual(['foo' => ['count' => 1]]);
});

test('test update row', function (): void {
    $this->readModel->stack('insert', 'foo', ['count' => 1]);
    $this->readModel->persist();

    $this->readModel->stack('update', 'foo', 'count', 2);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toEqual(['foo' => ['count' => 2]]);
});

test('test increment row', function (): void {
    $this->readModel->stack('insert', 'foo', ['count' => 1]);
    $this->readModel->persist();

    $this->readModel->stack('increment', 'foo', 'count', 5);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toEqual(['foo' => ['count' => 6]]);
});

test('decrement row', function (): void {
    $this->readModel->stack('insert', 'foo', ['count' => 6]);
    $this->readModel->persist();

    $this->readModel->stack('decrement', 'foo', 'count', 5);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toEqual(['foo' => ['count' => 1]]);
});

test('decrement row with extra fields', function () {
    $this->readModel->stack('insert', 'foo', ['count' => 5, 'created_at' => '2021-01-01']);
    $this->readModel->persist();

    $this->readModel->stack('decrement', 'foo', 'count', 5, ['created_at' => '2023-01-01']);
    $this->readModel->persist();

    $this->assertEquals(['foo' => ['count' => 0, 'created_at' => '2023-01-01']], $this->readModel->getContainer());
});

test('test delete row', function (): void {
    $this->readModel->stack('insert', 'foo', ['count' => 1]);
    $this->readModel->persist();
    $this->readModel->stack('delete', 'foo');
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toBeEmpty();
});

test('reset read model', function () {
    $this->readModel->stack('insert', 'foo', ['count' => 1]);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toEqual(['foo' => ['count' => 1]]);

    $this->readModel->reset();

    expect($this->readModel->getContainer())->toBeEmpty();
});

test('down read model', function () {
    $this->readModel->stack('insert', 'foo', ['count' => 1]);
    $this->readModel->persist();

    expect($this->readModel->getContainer())->toEqual(['foo' => ['count' => 1]]);

    $this->readModel->down();

    expect($this->readModel->getContainer())->toBeEmpty();
});
