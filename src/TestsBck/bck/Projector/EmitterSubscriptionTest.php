<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Projector\Subscription\AbstractPersistentSubscription;
use Chronhub\Storm\Projector\Subscription\AbstractSubscription;
use Chronhub\Storm\Projector\Subscription\EmitterSubscription;
use Chronhub\Storm\Stream\StreamName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(AbstractSubscription::class)]
#[CoversClass(AbstractPersistentSubscription::class)]
#[CoversClass(EmitterSubscription::class)]
final class EmitterSubscriptionTest extends PersistentSubscriptionTestCase
{
    private Chronicler|MockObject $chronicler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chronicler = $this->createMock(Chronicler::class);
    }

    public function testReviseEmitter(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('projectionName')
            ->willReturn('projection_name');

        $this->chronicler
            ->expects($this->once())
            ->method('delete')
            ->willReturnCallback(function (StreamName $streamName): void {
                $this->assertEquals('projection_name', $streamName->name);
            });

        $this->testRevise();
    }

    #[DataProvider('provideBoolean')]
    public function testDiscardEmitter(bool $withEmittedEvents): void
    {
        if ($withEmittedEvents) {
            $this->repository
                ->expects($this->once())
                ->method('projectionName')
                ->willReturn('projection_name');

            $this->chronicler
                ->expects($this->once())
                ->method('delete')
                ->willReturnCallback(function (StreamName $streamName): void {
                    $this->assertEquals('projection_name', $streamName->name);
                });
        } else {
            $this->repository
                ->expects($this->never())
                ->method('projectionName');

            $this->chronicler
                ->expects($this->never())
                ->method('delete');
        }

        $this->testDiscard($withEmittedEvents);
    }

    public function testFailSilentlyOnDiscard(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('projectionName')
            ->willReturn('projection_name');

        $this->chronicler
            ->expects($this->once())
            ->method('delete')
            ->willThrowException(new StreamNotFound('projection_name'));

        $this->testDiscard(true);
    }

    public function testLinkStream(): void
    {
        /** @var EmitterSubscriptionInterface $subscription */
        $subscription = $this->newSubscription();

        $this->assertFalse($subscription->wasEmitted());

        $subscription->eventEmitted();

        $this->assertTrue($subscription->wasEmitted());

        $subscription->unsetEmitted();

        $this->assertFalse($subscription->wasEmitted());
    }

    protected function defineSubscriptionType(): MockObject|ReadModel|Chronicler
    {
        return $this->chronicler;
    }
}
