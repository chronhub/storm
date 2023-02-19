<?php

declare(strict_types=1);

namespace Chronhub\Storm\Serializer;

use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

final class JsonSerializer
{
    final const CONTEXT = [JsonEncode::OPTIONS => JSON_FORCE_OBJECT];

    private readonly JsonEncoder $json;

    public function __construct()
    {
        //checkMe json throw
        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

        $this->json = new JsonEncoder(
            new JsonEncode([JsonEncode::OPTIONS => $jsonFlags]),
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
