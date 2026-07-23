<?php

namespace ParkWeb\Ase;

final class Scrubber
{
    private const REDACTED = '[REDACTED]';

    private const KEYS = [
        'authorization', 'cookie', 'set-cookie', 'password', 'password_confirmation',
        'token', 'access_token', 'refresh_token', 'secret', 'private_key', 'credit_card',
        'api_key', 'apikey', 'dsn',
    ];

    public function scrub(mixed $value): mixed
    {
        if (! is_array($value)) {
            return is_string($value) ? $this->scrubString($value) : $value;
        }

        $result = [];
        foreach ($value as $key => $child) {
            $result[$key] = in_array(strtolower((string) $key), self::KEYS, true)
                ? self::REDACTED
                : $this->scrub($child);
        }

        return $result;
    }

    private function scrubString(string $value): string
    {
        return preg_replace('/\b\d{13,19}\b/', self::REDACTED, $value) ?? $value;
    }
}
