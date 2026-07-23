<?php

namespace ParkWeb\Ase;

use ParkWeb\Ase\Transport\Transport;
use Throwable;

final class Client
{
    private Scope $scope;

    private EventFactory $events;

    public function __construct(
        private readonly ClientOptions $options,
        private readonly Transport $transport,
        ?Scope $scope = null,
    ) {
        $this->scope = $scope ?? new Scope;
        $this->events = new EventFactory($options, new Scrubber);
    }

    public function captureException(Throwable $throwable): ?string
    {
        return $this->capture($this->events->exception($throwable, $this->scope));
    }

    public function captureMessage(string $message, Level $level = Level::Info): ?string
    {
        return $this->capture($this->events->message($message, $level, $this->scope));
    }

    /** @param array<string, mixed> $user */
    public function setUser(array $user): void
    {
        $this->scope->setUser($user);
    }

    public function setTag(string $key, string $value): void
    {
        $this->scope->setTag($key, $value);
    }

    public function setExtra(string $key, mixed $value): void
    {
        $this->scope->setExtra($key, $value);
    }

    /** @param array<string, mixed> $breadcrumb */
    public function addBreadcrumb(array $breadcrumb): void
    {
        $this->scope->addBreadcrumb($breadcrumb);
    }

    public function withScope(callable $callback): mixed
    {
        $previous = $this->scope;
        $this->scope = $previous->clone();

        try {
            return $callback($this->scope, $this);
        } finally {
            $this->scope = $previous;
        }
    }

    public function flush(): void
    {
        $this->transport->flush();
    }

    /** @param array<string, mixed> $event */
    private function capture(array $event): ?string
    {
        if (! $this->options->enabled || $this->options->dsn === '') {
            return null;
        }
        if ($this->options->sampleRate < 1.0 && mt_rand() / mt_getrandmax() > $this->options->sampleRate) {
            return null;
        }

        try {
            $callback = $this->options->beforeSend;
            if (is_callable($callback)) {
                $event = $callback($event);
                if (! is_array($event)) {
                    return null;
                }
            }

            $this->transport->send($event);

            return (string) ($event['event_id'] ?? '');
        } catch (Throwable) {
            return null;
        }
    }
}
