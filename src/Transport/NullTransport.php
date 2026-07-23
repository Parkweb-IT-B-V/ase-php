<?php

namespace ParkWeb\Ase\Transport;

final class NullTransport implements Transport
{
    public function send(array $event): void {}

    public function sendBatch(array $events): void {}

    public function flush(): void {}
}
