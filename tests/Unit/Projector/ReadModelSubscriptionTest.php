<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Projector\AbstractPersistentSubscription;
use Chronhub\Storm\Projector\AbstractSubscription;
use Chronhub\Storm\Projector\ReadModelSubscription;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(AbstractSubscription::class)]
#[CoversClass(AbstractPersistentSubscription::class)]
#[CoversClass(ReadModelSubscription::class)]
final class ReadModelSubscriptionTest extends PersistentSubscriptionTestCase
{
   private ReadModel|MockObject $readModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->readModel = $this->createMock(ReadModel::class);
    }

    #[DataProvider('provideBoolean')]
    public function testRiseReadModel(bool $isInitialized): void
    {
        $this->readModel->expects($this->once())->method('isInitialized')->willReturn($isInitialized);

        $isInitialized
            ? $this->readModel->expects($this->never())->method('initialize')
            : $this->readModel->expects($this->once())->method('initialize');

        $this->testRise();
    }

    public function testStoreReadModel(): void
    {
        $this->readModel->expects($this->once())->method('persist');

        $this->testStore();
    }

    public function testReviseReadModel(): void
    {
        $this->readModel->expects($this->once())->method('reset');

        $this->testRevise();
    }

    #[DataProvider('provideBoolean')]
    public function testDiscardReadModel(bool $withEmittedEvent): void
    {
        $withEmittedEvent
            ? $this->readModel->expects($this->once())->method('down')
            : $this->readModel->expects($this->never())->method('down');

        $this->testDiscard($withEmittedEvent);
    }

    protected function defineSubscriptionType(): MockObject|ReadModel|Chronicler
    {
        return $this->readModel;
    }
}
