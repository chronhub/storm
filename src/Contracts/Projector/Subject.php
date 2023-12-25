<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface Subject
{
    public function register(Observer $observer): void;

    public function remove(Observer $observer): void;

    public function notify(): void;
}
