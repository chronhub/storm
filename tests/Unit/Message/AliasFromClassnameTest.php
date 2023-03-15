<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use InvalidArgumentException;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;

#[CoversClass(AliasFromClassName::class)]
final class AliasFromClassnameTest extends UnitTestCase
{
    #[Test]
    public function it_return_event_class_from_event_string(): void
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);

        $messageAlias = new AliasFromClassName();

        $this->assertEquals($event::class, $messageAlias->classToAlias($event::class));
    }

    #[Test]
    public function it_raise_exception_when_event_class_string_does_not_exists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Event class invalid_event does not exists');

        $messageAlias = new AliasFromClassname();

        $messageAlias->classToAlias('invalid_event');
    }

    #[Test]
    public function it_return_event_class_from_event_object(): void
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);

        $messageAlias = new AliasFromClassname();

        $this->assertEquals($event::class, $messageAlias->instanceToAlias($event));
    }
}
