<?php

namespace ParkWeb\Ase;

use InvalidArgumentException;

final readonly class Dsn
{
    public function __construct(
        public string $keyId,
        public string $secret,
        public string $endpoint,
    ) {}

    public static function parse(string $dsn): self
    {
        $parts = parse_url($dsn);
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'], $parts['user'])) {
            throw new InvalidArgumentException('Invalid ASE DSN. Expected https://token@host/api/v1/ingest/envelope or https://key:secret@host/api/v1/ingest/envelope');
        }

        $path = $parts['path'] ?? '/api/v1/ingest/event';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $user = rawurldecode((string) $parts['user']);
        $secret = isset($parts['pass']) ? rawurldecode((string) $parts['pass']) : $user;
        $keyId = isset($parts['pass']) ? $user : substr($secret, 0, 20);

        return new self(
            keyId: $keyId,
            secret: $secret,
            endpoint: $parts['scheme'].'://'.$parts['host'].$port.$path,
        );
    }
}
