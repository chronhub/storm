<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

abstract class ProphecyTestCase extends TestCase
{
    use ProphecyTrait;
}
