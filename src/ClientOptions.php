<?php

namespace ParkWeb\Ase;

final class ClientOptions
{
    /** @param array<string, mixed> $options */
    public function __construct(
        public readonly string $dsn,
        public readonly bool $enabled = true,
        public readonly ?string $release = null,
        public readonly ?string $environment = null,
        public readonly float $sampleRate = 1.0,
        public readonly float $timeout = 1.5,
        public readonly int $maxRetries = 1,
        public readonly bool $gzip = true,
        public readonly bool $sendDefaultPii = false,
        public readonly bool $captureWarnings = true,
        public readonly bool $debug = false,
        public readonly ?string $offlineBufferPath = null,
        public readonly mixed $beforeSend = null,
        public readonly array $options = [],
    ) {}

    /** @param array<string, mixed> $config */
    public static function fromArray(array $config): self
    {
        return new self(
            dsn: (string) ($config['dsn'] ?? ''),
            enabled: (bool) ($config['enabled'] ?? true),
            release: isset($config['release']) ? (string) $config['release'] : null,
            environment: isset($config['environment']) ? (string) $config['environment'] : null,
            sampleRate: (float) ($config['sample_rate'] ?? 1.0),
            timeout: (float) ($config['timeout'] ?? 1.5),
            maxRetries: (int) ($config['max_retries'] ?? 1),
            gzip: (bool) ($config['gzip'] ?? true),
            sendDefaultPii: (bool) ($config['send_default_pii'] ?? false),
            captureWarnings: (bool) ($config['capture_warnings'] ?? true),
            debug: (bool) ($config['debug'] ?? false),
            offlineBufferPath: isset($config['offline_buffer_path']) ? (string) $config['offline_buffer_path'] : null,
            beforeSend: $config['before_send'] ?? null,
            options: $config,
        );
    }
}
