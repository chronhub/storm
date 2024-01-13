<?php

declare(strict_types=1);

namespace Chronhub\Storm\Serializer;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Serializer\ContentSerializer;
use Chronhub\Storm\Contracts\Serializer\EventContentSerializer;
use Chronhub\Storm\Contracts\Serializer\MessageSerializer;
use Chronhub\Storm\Contracts\Serializer\StreamEventSerializer;
use Closure;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

use function array_map;
use function array_merge;
use function is_string;

class JsonSerializerFactory
{
    protected ContainerInterface $container;

    public function __construct(Closure $container)
    {
        $this->container = $container();
    }

    public function createMessageSerializer(
        ?ContentSerializer $contentSerializer = null,
        NormalizerInterface|DenormalizerInterface|string ...$normalizers
    ): MessageSerializer {
        $contentSerializer ??= new MessageContentSerializer();

        $symfonySerializer = $this->getSymfonySerializer(...$normalizers);

        return new MessagingSerializer($contentSerializer, $symfonySerializer);
    }

    public function createStreamSerializer(
        ?EventContentSerializer $contentSerializer = null,
        NormalizerInterface|DenormalizerInterface|string ...$normalizers
    ): StreamEventSerializer {
        $contentSerializer ??= new DomainEventContentSerializer();

        $symfonySerializer = $this->getSymfonySerializer(...$normalizers);

        return new DomainEventSerializer($contentSerializer, $symfonySerializer);
    }

    protected function getSymfonySerializer(string|NormalizerInterface|DenormalizerInterface ...$normalizers): Serializer
    {
        $normalizers = array_merge($normalizers, $this->getDefaultNormalizers());

        $jsonEncoder = $this->getJsonEncoder();

        return new Serializer($this->resolveNormalizers(...$normalizers), [$jsonEncoder]);
    }

    protected function getJsonEncoder(): JsonEncoder
    {
        return (new SerializeToJson())->getEncoder();
    }

    /**
     * @return array<NormalizerInterface|DenormalizerInterface>
     */
    protected function getDefaultNormalizers(): array
    {
        return [
            new DateTimeNormalizer([
                DateTimeNormalizer::FORMAT_KEY => $this->container->get(SystemClock::class)->getFormat(),
                DateTimeNormalizer::TIMEZONE_KEY => 'UTC',
            ]),
        ];
    }

    protected function resolveNormalizers(NormalizerInterface|DenormalizerInterface|string ...$normalizers): array
    {
        return array_map(function ($normalizer): NormalizerInterface|DenormalizerInterface {
            if (is_string($normalizer)) {
                return $this->container->get($normalizer);
            }

            return $normalizer;
        }, $normalizers);
    }
}
