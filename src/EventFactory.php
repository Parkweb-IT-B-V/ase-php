<?php

namespace ParkWeb\Ase;

use Throwable;

final readonly class EventFactory
{
    public function __construct(private ClientOptions $options, private Scrubber $scrubber) {}

    public function exception(Throwable $throwable, Scope $scope): array
    {
        return $this->base(Level::Error, $throwable->getMessage(), $scope) + [
            'exception' => [
                'type' => $throwable::class,
                'message' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'stacktrace' => ['frames' => $this->frames($throwable->getTrace())],
            ],
        ];
    }

    public function message(string $message, Level $level, Scope $scope): array
    {
        return $this->base($level, $message, $scope);
    }

    /** @param array<int, array<string, mixed>> $trace */
    private function frames(array $trace): array
    {
        return array_map(fn (array $frame): array => [
            'filename' => isset($frame['file']) ? (string) $frame['file'] : null,
            'function' => isset($frame['function']) ? (string) $frame['function'] : null,
            'line' => isset($frame['line']) ? (int) $frame['line'] : null,
            'column' => null,
        ], array_slice($trace, -100));
    }

    /** @return array<string, mixed> */
    private function base(Level $level, string $message, Scope $scope): array
    {
        $payload = [
            'event_id' => 'evt_'.bin2hex(random_bytes(16)),
            'occurred_at' => gmdate(DATE_ATOM),
            'origin' => 'server',
            'level' => $level->value,
            'platform' => 'php',
            'message' => mb_substr($message, 0, 8192),
            'release' => $this->options->release,
            'sdk' => ['name' => 'parkweb/ase-php', 'version' => '0.1.0'],
            'runtime' => ['name' => 'php', 'version' => PHP_VERSION],
        ] + $scope->toPayload();

        return $this->scrubber->scrub($payload);
    }
}
