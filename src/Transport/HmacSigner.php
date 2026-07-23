<?php

namespace ParkWeb\Ase\Transport;

use ParkWeb\Ase\Dsn;

final readonly class HmacSigner
{
    /** @return array<string, string> */
    public function headers(Dsn $dsn, string $method, string $path, string $body): array
    {
        $timestamp = (string) time();
        $nonce = bin2hex(random_bytes(12));
        $canonical = strtoupper($method)."\n".$path."\n".$timestamp."\n".$nonce."\n".hash('sha256', $body);

        return [
            'X-ASE-Key-Id' => $dsn->keyId,
            'X-ASE-Timestamp' => $timestamp,
            'X-ASE-Nonce' => $nonce,
            'X-ASE-Signature' => hash_hmac('sha256', $canonical, $dsn->secret),
        ];
    }
}
