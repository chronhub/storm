<?php

declare(strict_types=1);

namespace Chronhub\Storm\Serializer;

use Closure;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Serializer;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Serializer\ContentSerializer;
use Chronhub\Storm\Contracts\Serializer\MessageSerializer;
use Chronhub\Storm\Contracts\Serializer\StreamEventSerializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use function array_map;
use function is_string;

class JsonSerializerFactory
{
    protected ContainerInterface $container;

    public function __construct(Closure $container)
    {
        $this->container = $container();
    }

    public function createMessageSerializer(?ContentSerializer $contentSerializer = null,
                                            NormalizerInterface|DenormalizerInterface|string ...$normalizers): MessageSerializer
    {
        $symfonySerializer = $this->getSerializer(
            ...$this->resolveNormalizers(...$normalizers)
        );

        $contentSerializer ??= new MessagingContentSerializer();

        return new MessagingSerializer($contentSerializer, $symfonySerializer);
    }

    public function createStreamSerializer(?ContentSerializer $contentSerializer = null,
                                           NormalizerInterface|DenormalizerInterface|string ...$normalizers): StreamEventSerializer
    {
        $symfonySerializer = $this->getSerializer(
            ...$this->resolveNormalizers(...$normalizers)
        );

        $contentSerializer ??= new MessagingContentSerializer();

        return new DomainEventSerializer($contentSerializer, $symfonySerializer);
    }

    protected function dateTimeNormalizer(): DateTimeNormalizer
    {
        return new DateTimeNormalizer([
            DateTimeNormalizer::FORMAT_KEY => $this->container->get(SystemClock::class)->getFormat(),
            DateTimeNormalizer::TIMEZONE_KEY => 'UTC',
        ]);
    }

    protected function getSerializer(NormalizerInterface|DenormalizerInterface ...$normalizers): Serializer
    {
        $normalizers[] = $this->dateTimeNormalizer();

        return new Serializer($normalizers, [(new SerializeToJson())->getEncoder()]);
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
