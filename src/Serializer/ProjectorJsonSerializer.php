<?php

declare(strict_types=1);

namespace Chronhub\Storm\Serializer;

use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;

final readonly class ProjectorJsonSerializer implements JsonSerializer
{
    final public const ENCODE_OPTIONS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_FORCE_OBJECT;

    final public const DECODE_OPTIONS = JSON_BIGINT_AS_STRING | JSON_OBJECT_AS_ARRAY;

    public JsonEncoder $json;

    public function __construct()
    {
        $this->json = new JsonEncoder(
            new JsonEncode([JsonEncode::OPTIONS => self::ENCODE_OPTIONS]),
            new JsonDecode([JsonDecode::OPTIONS => self::DECODE_OPTIONS])
        );
    }

    public function encode(mixed $data, array $context = []): string
    {
        return $this->json->encode($data, 'json', $context);
    }

    public function decode(string $data): mixed
    {
        return $this->json->decode($data, 'json');
    }
}
