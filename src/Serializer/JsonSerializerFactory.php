<?php

declare(strict_types=1);

namespace Chronhub\Storm\Serializer;

use Symfony\Component\Serializer\Serializer;
use Chronhub\Storm\Contracts\Serializer\ContentSerializer;
use Chronhub\Storm\Contracts\Serializer\MessageSerializer;
use Chronhub\Storm\Contracts\Serializer\StreamEventSerializer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class JsonSerializerFactory
{
    public function createForMessaging(?ContentSerializer $contentSerializer = null,
                                       NormalizerInterface|DenormalizerInterface ...$normalizers): MessageSerializer
    {
        $symfonySerializer = new Serializer($normalizers, [(new SerializeToJson())->getEncoder()]);

        $contentSerializer ??= new MessagingContentSerializer();

        return new MessagingSerializer($contentSerializer, $symfonySerializer);
    }

    public function createForStream(?ContentSerializer $contentSerializer = null,
                                    NormalizerInterface|DenormalizerInterface ...$normalizers): StreamEventSerializer
    {
        $symfonySerializer = new Serializer($normalizers, [(new SerializeToJson())->getEncoder()]);

        $contentSerializer ??= new MessagingContentSerializer();

        return new DomainEventSerializer($contentSerializer, $symfonySerializer);
    }
}
