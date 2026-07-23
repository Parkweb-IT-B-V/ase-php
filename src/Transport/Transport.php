<?php

namespace ParkWeb\Ase\Transport;

interface Transport
{
    /** @param array<string, mixed> $event */
    public function send(array $event): void;

    /** @param array<int, array<string, mixed>> $events */
    public function sendBatch(array $events): void;

    public function flush(): void;
}
