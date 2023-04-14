<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Projector\EmitterSubscription;
use Chronhub\Storm\Stream\StreamName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

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

        $this->assertFalse($subscription->isJoined());

        $subscription->join();

        $this->assertTrue($subscription->isJoined());

        $subscription->disjoin();

        $this->assertFalse($subscription->isJoined());
    }

    protected function defineSubscriptionType(): MockObject|ReadModel|Chronicler
    {
        return $this->chronicler;
    }
}