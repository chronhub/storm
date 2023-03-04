<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Projector\ReadModel\InMemoryReadModel;
use function abs;

final class InMemoryReadModelTest extends UnitTestCase
{
    #[Test]
    public function it_can_be_initialized(): void
    {
        $readModel = new InMemoryReadModel();

        $this->assertTrue($readModel->isInitialized());

        $readModel->initialize();

        $this->assertEquals([], $readModel->getContainer());
    }

    #[Test]
    public function it_can_insert_data(): void
    {
        $readModel = new InMemoryReadModel();

        $readModel->stack('insert', '123-', ['foo' => 'bar']);

        $this->assertEmpty($readModel->getContainer());

        $readModel->persist();

        $this->assertEquals(['123-' => ['foo' => 'bar']], $readModel->getContainer());
    }

    #[Test]
    public function it_can_update_data(): void
    {
        $readModel = new InMemoryReadModel();

        $readModel->stack('insert', '123-', ['foo' => 'bar']);

        $this->assertEmpty($readModel->getContainer());

        $readModel->persist();

        $this->assertEquals(['123-' => ['foo' => 'bar']], $readModel->getContainer());

        $readModel->stack('update', '123-', 'foo', 'bar123');

        $readModel->persist();

        $this->assertEquals(['123-' => ['foo' => 'bar123']], $readModel->getContainer());
    }

    #[DataProvider('provideIncrementAbsoluteValue')]
    #[Test]
    public function it_can_increment_field_by_absolute_value(int|float $value): void
    {
        $readModel = new InMemoryReadModel();

        $readModel->stack('insert', '123-', ['foo' => 5, 'extra' => 0]);

        $this->assertEmpty($readModel->getContainer());

        $readModel->persist();

        $this->assertEquals(['123-' => ['foo' => 5, 'extra' => 0]], $readModel->getContainer());

        $readModel->stack('increment', '123-', 'foo', $value, ['extra' => 1]);

        $readModel->persist();

        $this->assertEquals(['123-' => ['foo' => 5 + abs($value), 'extra' => 1]], $readModel->getContainer());
    }

    #[DataProvider('provideIncrementAbsoluteValue')]
    #[Test]
    public function it_can_decrement_field_by_absolute_value(int|float $value): void
    {
        $readModel = new InMemoryReadModel();

        $readModel->stack('insert', '123-', ['foo' => 5, 'extra' => 0]);

        $this->assertEmpty($readModel->getContainer());

        $readModel->persist();

        $this->assertEquals(['123-' => ['foo' => 5, 'extra' => 0]], $readModel->getContainer());

        $readModel->stack('decrement', '123-', 'foo', $value, ['extra' => 1]);

        $readModel->persist();

        $this->assertEquals(['123-' => ['foo' => 5 - abs($value), 'extra' => 1]], $readModel->getContainer());
    }

    #[Test]
    public function it_can_delete(): void
    {
        $readModel = new InMemoryReadModel();

        $readModel->stack('insert', '123-', ['foo' => 5]);
        $readModel->stack('insert', '124-', ['foo' => 5]);
        $readModel->stack('insert', '125-', ['foo' => 5]);

        $readModel->persist();

        $readModel->stack('delete', '124-');

        $readModel->persist();

        $this->assertEquals([
            '123-' => ['foo' => 5],
            '125-' => ['foo' => 5],
        ], $readModel->getContainer());
    }

    #[Test]
    public function it_can_down_container(): void
    {
        $readModel = new InMemoryReadModel();

        $readModel->stack('insert', '123-', ['foo' => 'bar']);

        $this->assertEmpty($readModel->getContainer());

        $readModel->persist();

        $this->assertEquals(['123-' => ['foo' => 'bar']], $readModel->getContainer());

        $readModel->down();

        $this->assertEmpty($readModel->getContainer());
    }

    #[Test]
    public function it_can_reset_container(): void
    {
        $readModel = new InMemoryReadModel();

        $readModel->stack('insert', '123-', ['foo' => 'bar']);

        $this->assertEmpty($readModel->getContainer());

        $readModel->persist();

        $this->assertEquals(['123-' => ['foo' => 'bar']], $readModel->getContainer());

        $readModel->reset();

        $this->assertEmpty($readModel->getContainer());
    }

    public static function provideIncrementAbsoluteValue(): Generator
    {
        yield [5];
        yield [-5];
        yield [5.5];
        yield [-5.5];
    }
}
