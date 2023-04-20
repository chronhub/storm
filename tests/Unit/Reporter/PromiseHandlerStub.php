<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use React\Promise\PromiseInterface;
use Throwable;

final class PromiseHandlerStub
{
    public function handlePromise(PromiseInterface $promise, bool $raiseException): mixed
    {
        $exception = null;
        $result = null;

        $promise->then(
            static function ($data) use (&$result): void {
                $result = $data;
            },
            static function ($exc) use (&$exception): void {
                $exception = $exc;
            }
        );

        if ($raiseException && $exception instanceof Throwable) {
            throw $exception;
        }

        return $exception ?? $result;
    }
}
