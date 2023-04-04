<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Message\AliasFromInflector;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\UnitTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(AliasFromInflector::class)]
final class AliasFromInflectorTest extends UnitTestCase
{
    #[Test]
    public function it_return_event_class_from_event_string(): void
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);

        $messageAlias = new AliasFromInflector();

        $this->assertEquals('some-command', $messageAlias->classToAlias($event::class));
    }

    #[Test]
    public function it_raise_exception_when_event_class_string_does_not_exists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Event class invalid_event does not exists');

        $messageAlias = new AliasFromInflector();

        $messageAlias->classToAlias('invalid_event');
    }

    #[Test]
    public function it_return_alias_from_event_object(): void
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);

        $messageAlias = new AliasFromInflector();

        $this->assertEquals('some-command', $messageAlias->instanceToAlias($event));
    }
}
