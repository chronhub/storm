<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\CommandGroup;
use Chronhub\Storm\Routing\CollectRoutes;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Message\AliasFromClassName;

#[CoversClass(CommandGroup::class)]
final class CommandGroupTest extends UnitTestCase
{
    public function testGroupType(): void
    {
        $routes = new CollectRoutes(new AliasFromClassName());

        $this->assertEquals(DomainType::COMMAND, (new CommandGroup('default', $routes))->getType());
    }
}
