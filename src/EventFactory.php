<?php

namespace ParkWeb\Ase;

use Throwable;

final readonly class EventFactory
{
    public function __construct(private ClientOptions $options, private Scrubber $scrubber) {}

    public function exception(Throwable $throwable, Scope $scope): array
    {
        return $this->base($this->levelFor($throwable), $throwable->getMessage(), $scope) + [
            'exception' => [
                'type' => $throwable::class,
                'message' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'mechanism' => $this->mechanism($throwable),
                'stacktrace' => ['frames' => $this->frames($throwable->getTrace(), $throwable)],
            ],
        ];
    }

    public function message(string $message, Level $level, Scope $scope): array
    {
        return $this->base($level, $message, $scope);
    }

    /** @param array<string, mixed> $extra */
    public function telemetry(string $type, string $message, array $extra, Scope $scope, Level $level = Level::Info): array
    {
        return $this->base($level, $message, $scope, [
            'telemetry_type' => $type,
            'telemetry' => $extra,
        ]);
    }

    /** @param array<int, array<string, mixed>> $trace */
    private function frames(array $trace, ?Throwable $throwable = null): array
    {
        $frames = [];
        if ($throwable !== null && $throwable->getFile() !== '') {
            $frames[] = $this->frame($throwable->getFile(), null, $throwable->getLine());
        }

        foreach (array_slice($trace, -99) as $frame) {
            $frames[] = $this->frame(
                isset($frame['file']) ? (string) $frame['file'] : null,
                isset($frame['function']) ? (string) $frame['function'] : null,
                isset($frame['line']) ? (int) $frame['line'] : null,
            );
        }

        return $frames;
    }

    private function frame(?string $filename, ?string $function, ?int $line): array
    {
        $frame = [
            'filename' => $filename,
            'function' => $function,
            'line' => $line,
            'column' => null,
        ];

        if (is_string($filename) && $filename !== '') {
            $view = $this->compiledBladeSource($filename);
            if ($view !== null) {
                $frame['view'] = $view;
            }
        }

        return $frame;
    }

    private function levelFor(Throwable $throwable): Level
    {
        if (! $throwable instanceof \ErrorException) {
            return Level::Error;
        }

        return match ($throwable->getSeverity()) {
            E_DEPRECATED, E_USER_DEPRECATED, E_WARNING, E_USER_WARNING, E_NOTICE, E_USER_NOTICE, E_STRICT => Level::Warning,
            default => Level::Error,
        };
    }

    /** @return array<string, mixed> */
    private function mechanism(Throwable $throwable): array
    {
        if (! $throwable instanceof \ErrorException) {
            return ['type' => 'exception', 'handled' => false];
        }

        return [
            'type' => 'php_error',
            'handled' => false,
            'severity' => $throwable->getSeverity(),
            'severity_name' => $this->severityName($throwable->getSeverity()),
        ];
    }

    private function severityName(int $severity): string
    {
        return match ($severity) {
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            default => 'E_UNKNOWN',
        };
    }

    /** @return array{compiled: string, source: string}|null */
    private function compiledBladeSource(string $filename): ?array
    {
        $normalized = str_replace('\\', '/', $filename);
        if (! str_contains($normalized, '/storage/framework/views/') || ! is_file($filename) || ! is_readable($filename)) {
            return null;
        }

        $contents = file_get_contents($filename);
        if (! is_string($contents) || $contents === '') {
            return ['compiled' => $filename, 'source' => 'unknown'];
        }

        if (preg_match('/\/\*\*PATH\s+(.+?)\s+ENDPATH\*\*\//', $contents, $matches) === 1) {
            return ['compiled' => $filename, 'source' => $matches[1]];
        }

        return ['compiled' => $filename, 'source' => 'unknown'];
    }

    /** @return array<string, mixed> */
    /** @param array<string, mixed> $extra */
    private function base(Level $level, string $message, Scope $scope, array $extra = []): array
    {
        $payload = [
            'event_id' => 'evt_'.bin2hex(random_bytes(16)),
            'occurred_at' => gmdate(DATE_ATOM),
            'origin' => 'server',
            'level' => $level->value,
            'platform' => 'php',
            'message' => mb_substr($message, 0, 8192),
            'release' => $this->options->release,
            'sdk' => ['name' => 'parkweb/ase-php', 'version' => '0.1.3'],
            'runtime' => ['name' => 'php', 'version' => PHP_VERSION],
        ] + $scope->toPayload();

        if ($extra !== []) {
            $payload['extra'] = array_merge($payload['extra'] ?? [], $extra);
        }

        return $this->scrubber->scrub($payload);
    }
}
