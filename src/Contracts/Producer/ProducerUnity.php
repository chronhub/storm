<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Producer;

use Chronhub\Storm\Message\Message;

interface ProducerUnity
{
    /**
     * @param  Message  $message
     * @return bool
     */
    public function isSync(Message $message): bool;
}
