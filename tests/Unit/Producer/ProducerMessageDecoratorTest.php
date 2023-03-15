<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Producer;

use Generator;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Producer\ProducerStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Producer\ProducerMessageDecorator;

final class ProducerMessageDecoratorTest extends UnitTestCase
{
    #[DataProvider('provideProducerStrategy')]
    #[Test]
    public function it_decorate_message_with_event_strategy_and_dispatched_headers(ProducerStrategy $producerStrategy): void
    {
        $message = new Message(SomeCommand::fromContent(['name' => 'steph bug']));

        $messageDecorator = new ProducerMessageDecorator($producerStrategy);

        $decoratedMessage = $messageDecorator->decorate($message);

        $this->assertNotEquals($message, $decoratedMessage);

        $this->assertEquals([
            Header::EVENT_STRATEGY => $producerStrategy->value,
            Header::EVENT_DISPATCHED => false,
        ], $decoratedMessage->headers());
    }

    public static function provideProducerStrategy(): Generator
    {
        yield [ProducerStrategy::SYNC];
        yield [ProducerStrategy::ASYNC];
        yield [ProducerStrategy::PER_MESSAGE];
    }
}
