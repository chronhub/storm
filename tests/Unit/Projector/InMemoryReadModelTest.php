<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Projector\ReadModel\InMemoryReadModel;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InMemoryReadModel::class)]
final class InMemoryReadModelTest extends UnitTestCase
{
    private InMemoryReadModel $readModel;

    protected function setUp(): void
    {
        $this->readModel = new InMemoryReadModel();
    }

    public function testInstance(): void
    {
        $this->assertEmpty($this->readModel->getContainer());
    }

    public function testAlreadyInitialized(): void
    {
        $this->assertTrue($this->readModel->isInitialized());

        $this->readModel->initialize();

        $this->assertTrue($this->readModel->isInitialized());
    }

    public function testInsert(): InMemoryReadModel
    {
        $this->readModel->stack('insert', 'foo', ['count' => 1]);
        $this->readModel->persist();

        $this->assertEquals(['foo' => ['count' => 1]], $this->readModel->getContainer());

        return $this->readModel;
    }

    public function testUpdateRow(): void
    {
        $this->readModel->stack('insert', 'foo', ['count' => 1]);
        $this->readModel->persist();
        $this->readModel->stack('update', 'foo', 'count', 2);
        $this->readModel->persist();

        $this->assertEquals(['foo' => ['count' => 2]], $this->readModel->getContainer());
    }

    public function testIncrementRow(): void
    {
        $this->readModel->stack('insert', 'foo', ['count' => 1]);
        $this->readModel->persist();
        $this->readModel->stack('increment', 'foo', 'count', 5);
        $this->readModel->persist();

        $this->assertEquals(['foo' => ['count' => 6]], $this->readModel->getContainer());
    }

    public function testIncrementRowWithExtra(): void
    {
        $this->readModel->stack('insert', 'foo', ['count' => 1, 'created_at' => '2021-01-01']);
        $this->readModel->persist();
        $this->readModel->stack('increment', 'foo', 'count', 5, ['created_at' => '2023-01-01']);
        $this->readModel->persist();

        $this->assertEquals(['foo' => ['count' => 6, 'created_at' => '2023-01-01']], $this->readModel->getContainer());
    }

    public function testDecrementRow(): void
    {
        $this->readModel->stack('insert', 'foo', ['count' => 5]);
        $this->readModel->persist();
        $this->readModel->stack('decrement', 'foo', 'count', 2);
        $this->readModel->persist();

        $this->assertEquals(['foo' => ['count' => 3]], $this->readModel->getContainer());
    }

    public function testDecrementRowWithExtra(): void
    {
        $this->readModel->stack('insert', 'foo', ['count' => 5, 'created_at' => '2021-01-01']);
        $this->readModel->persist();
        $this->readModel->stack('decrement', 'foo', 'count', 5, ['created_at' => '2023-01-01']);
        $this->readModel->persist();

        $this->assertEquals(['foo' => ['count' => 0, 'created_at' => '2023-01-01']], $this->readModel->getContainer());
    }

    public function testDeleteRow(): void
    {
        $this->readModel->stack('insert', 'foo', ['count' => 5]);
        $this->readModel->persist();
        $this->readModel->stack('delete', 'foo');
        $this->readModel->persist();

        $this->assertEmpty($this->readModel->getContainer());
    }

    public function testResetReadModel(): void
    {
        $this->readModel->stack('insert', 'foo', ['count' => 5]);
        $this->readModel->persist();
        $this->readModel->reset();

        $this->assertEmpty($this->readModel->getContainer());
    }

    public function testDownReadModel(): void
    {
        $this->readModel->stack('insert', 'foo', ['count' => 5]);
        $this->readModel->persist();
        $this->readModel->down();

        $this->assertEmpty($this->readModel->getContainer());
    }
}
