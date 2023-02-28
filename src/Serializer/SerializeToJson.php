<?php

declare(strict_types=1);

namespace Chronhub\Storm\Serializer;

use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;

final readonly class SerializeToJson implements JsonSerializer
{
    public JsonEncoder $json;

    public function __construct()
    {
        $this->json = new JsonEncoder(
            new JsonEncode([JsonEncode::OPTIONS => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION]),
            new JsonDecode([JsonDecode::OPTIONS => JSON_BIGINT_AS_STRING])
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

    public function getEncoder(): JsonEncoder
    {
        return $this->json;
    }
}
