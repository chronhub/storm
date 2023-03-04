<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Producer;

use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Producer\ProduceMessage;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Tests\Double\SomeCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Chronhub\Storm\Contracts\Producer\MessageQueue;
use Chronhub\Storm\Contracts\Producer\ProducerUnity;

final class ProduceMessageTest extends UnitTestCase
{
    private MockObject|ProducerUnity $unity;

    private MockObject|MessageQueue $queue;

    protected function setup(): void
    {
        $this->unity = $this->createMock(ProducerUnity::class);
        $this->queue = $this->createMock(MessageQueue::class);
    }

    /**
     * @test
     */
    public function it_produce_message_sync(): void
    {
        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [Header::EVENT_DISPATCHED => false]);

        $this->unity->expects($this->once())
            ->method('isSync')
            ->with($message)
            ->willReturn(true);

        $this->queue->expects($this->never())->method('toQueue');

        $producer = new ProduceMessage($this->unity, $this->queue);

        $dispatchedMessage = $producer->produce($message);

        $this->assertTrue($dispatchedMessage->header(Header::EVENT_DISPATCHED));
    }

    /**
     * @test
     */
    public function it_produce_message_async(): void
    {
        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [Header::EVENT_DISPATCHED => false]);

        $this->unity->expects($this->once())
            ->method('isSync')
            ->with($message)
            ->willReturn(false);

        $this->queue->expects($this->once())
            ->method('toQueue')
            ->with($this->callback(function (Message $message): bool {
                $this->assertTrue($message->header(Header::EVENT_DISPATCHED));

                return true;
            }));

        $producer = new ProduceMessage($this->unity, $this->queue);

        $dispatchedMessage = $producer->produce($message);

        $this->assertTrue($dispatchedMessage->header(Header::EVENT_DISPATCHED));
    }
}
