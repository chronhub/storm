<?php

declare(strict_types=1);

namespace Chronhub\Storm\Producer;

use InvalidArgumentException;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Message\AsyncMessage;
use Chronhub\Storm\Contracts\Producer\ProducerUnity;
use function is_bool;
use function is_string;

final class LogicalProducer implements ProducerUnity
{
    public function isSync(Message $message): bool
    {
        [$alreadyDispatched, $strategy] = $this->validateHeaders($message->headers());

        if ($alreadyDispatched || $strategy === ProducerStrategy::SYNC->value) {
            return true;
        }

        if ($strategy === ProducerStrategy::PER_MESSAGE->value && ! $message->event() instanceof AsyncMessage) {
            return true;
        }

        return false;
    }

    /**
     * @return array{bool, string}
     */
    private function validateHeaders(array $headers): array
    {
        $eventDispatched = $headers[Header::EVENT_DISPATCHED] ?? null;

        if (! is_bool($eventDispatched)) {
            throw new InvalidArgumentException('Invalid producer event header: "__event_dispatched" is required and must be a boolean');
        }

        $eventStrategy = $headers[Header::EVENT_STRATEGY] ?? null;

        if (! is_string($eventStrategy)) {
            throw new InvalidArgumentException('Invalid producer event header: "__event_strategy" is required and must be a string');
        }

        $eventStrategy = ProducerStrategy::from($eventStrategy)->value;

        return [$eventDispatched, $eventStrategy];
    }
}
