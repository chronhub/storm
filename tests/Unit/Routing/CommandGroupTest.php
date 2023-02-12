<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\CommandGroup;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Message\AliasFromClassName;

final class CommandGroupTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_assert_type(): void
    {
        $routes = new CollectRoutes(new AliasFromClassName());

        $this->assertEquals(DomainType::COMMAND, (new CommandGroup('default', $routes))->getType());
    }
}
