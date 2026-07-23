<?php

namespace ParkWeb\Ase;

use Psr\Log\AbstractLogger;
use Stringable;

final class PsrLogHandler extends AbstractLogger
{
    public function __construct(private readonly Client $client) {}

    /** @param array<string, mixed> $context */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $aseLevel = match ((string) $level) {
            'emergency', 'alert', 'critical' => Level::Fatal,
            'error' => Level::Error,
            'warning' => Level::Warning,
            'notice', 'info' => Level::Info,
            default => Level::Debug,
        };

        if (($context['exception'] ?? null) instanceof \Throwable) {
            $this->client->captureException($context['exception']);

            return;
        }

        $this->client->withScope(function (Scope $scope) use ($context, $message, $aseLevel): void {
            foreach ($context as $key => $value) {
                if ($key !== 'exception') {
                    $scope->setExtra((string) $key, $value);
                }
            }
            $this->client->captureMessage((string) $message, $aseLevel);
        });
    }
}
