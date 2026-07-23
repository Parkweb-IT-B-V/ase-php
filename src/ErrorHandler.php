<?php

namespace ParkWeb\Ase;

use ErrorException;

final readonly class ErrorHandler
{
    public function __construct(private Client $client, private bool $captureWarnings = true) {}

    public function register(): void
    {
        set_exception_handler(fn (\Throwable $throwable): bool => $this->handleException($throwable));
        register_shutdown_function(fn () => $this->handleShutdown());
        if ($this->captureWarnings) {
            set_error_handler(fn (int $severity, string $message, string $file, int $line): bool => $this->handleError($severity, $message, $file, $line));
        }
    }

    private function handleException(\Throwable $throwable): bool
    {
        $this->client->captureException($throwable);
        $this->client->flush();

        return false;
    }

    private function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (! (error_reporting() & $severity)) {
            return false;
        }

        $this->client->captureException(new ErrorException($message, 0, $severity, $file, $line));

        return false;
    }

    private function handleShutdown(): void
    {
        $error = error_get_last();
        if (! is_array($error) || ! in_array($error['type'] ?? null, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        $this->client->captureException(new ErrorException((string) $error['message'], 0, (int) $error['type'], (string) $error['file'], (int) $error['line']));
        $this->client->flush();
    }
}
