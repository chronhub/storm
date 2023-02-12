<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Producer;

use Prophecy\Argument;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Producer\ProduceMessage;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Tests\Double\SomeCommand;
use Chronhub\Storm\Contracts\Producer\MessageQueue;
use Chronhub\Storm\Contracts\Producer\ProducerUnity;

final class ProduceMessageTest extends ProphecyTestCase
{
    /**
     * @test
     */
    public function it_produce_message_sync(): void
    {
        $unity = $this->prophesize(ProducerUnity::class);
        $queue = $this->prophesize(MessageQueue::class);

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [Header::EVENT_DISPATCHED => false]);

        $unity->isSync($message)->willReturn(true)->shouldBeCalledOnce();
        $queue->toQueue($message)->shouldNotBeCalled();

        $producer = new ProduceMessage($unity->reveal(), $queue->reveal());

        $dispatchedMessage = $producer->produce($message);

        $this->assertTrue($dispatchedMessage->header(Header::EVENT_DISPATCHED));
    }

    /**
     * @test
     */
    public function it_produce_message_async(): void
    {
        $unity = $this->prophesize(ProducerUnity::class);
        $queue = $this->prophesize(MessageQueue::class);

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [Header::EVENT_DISPATCHED => false]);

        $unity->isSync($message)->willReturn(false)->shouldBeCalledOnce();

        $queue->toQueue(Argument::that(function (Message $message): Message {
            $this->assertTrue($message->header(Header::EVENT_DISPATCHED));

            return $message;
        }))->shouldBeCalledOnce();

        $producer = new ProduceMessage($unity->reveal(), $queue->reveal());

        $dispatchedMessage = $producer->produce($message);

        $this->assertTrue($dispatchedMessage->header(Header::EVENT_DISPATCHED));
    }
}
