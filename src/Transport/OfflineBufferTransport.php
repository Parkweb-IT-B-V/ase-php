<?php

namespace ParkWeb\Ase\Transport;

final readonly class OfflineBufferTransport implements Transport
{
    public function __construct(private string $path) {}

    public function send(array $event): void
    {
        $this->sendBatch([$event]);
    }

    public function sendBatch(array $events): void
    {
        if ($events === []) {
            return;
        }

        $directory = dirname($this->path);
        if (! is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        foreach ($events as $event) {
            @file_put_contents($this->path, json_encode($event).PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    public function flush(): void {}
}
