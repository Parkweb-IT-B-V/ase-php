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
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'], $parts['user'], $parts['pass'])) {
            throw new InvalidArgumentException('Invalid ASE DSN. Expected https://key:secret@host/api/v1/ingest/event');
        }

        $path = $parts['path'] ?? '/api/v1/ingest/event';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return new self(
            keyId: rawurldecode((string) $parts['user']),
            secret: rawurldecode((string) $parts['pass']),
            endpoint: $parts['scheme'].'://'.$parts['host'].$port.$path,
        );
    }
}
