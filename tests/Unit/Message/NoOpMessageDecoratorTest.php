<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Message\NoOpMessageDecorator;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

final class NoOpMessageDecoratorTest extends UnitTestCase
{
    #[DataProvider('provideEvent')]
    #[Test]
    public function it_return_same_message(object $event): void
    {
        $message = new Message($event);

        $messageDecorator = new NoOpMessageDecorator();

        $this->assertSame($message, $messageDecorator->decorate($message));
    }

    public static function provideEvent(): Generator
    {
        yield [new stdClass()];
        yield [new SomeCommand(['foo' => 'bar'])];
        yield [(new SomeEvent(['foo' => 'bar']))->withHeaders(['some' => 'header'])];
    }
}
