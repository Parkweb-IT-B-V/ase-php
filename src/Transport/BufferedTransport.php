<?php

namespace ParkWeb\Ase\Transport;

final class BufferedTransport implements Transport
{
    /** @var array<int, array<string, mixed>> */
    private array $buffer = [];

    public function __construct(private readonly Transport $inner, private readonly int $batchSize = 10) {}

    public function send(array $event): void
    {
        $this->buffer[] = $event;
        if (count($this->buffer) >= $this->batchSize) {
            $this->flush();
        }
    }

    public function sendBatch(array $events): void
    {
        foreach ($events as $event) {
            $this->send($event);
        }
    }

    public function flush(): void
    {
        if ($this->buffer === []) {
            return;
        }

        $events = $this->buffer;
        $this->buffer = [];
        $this->inner->sendBatch($events);
    }
}
