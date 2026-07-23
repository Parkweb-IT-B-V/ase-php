<?php

namespace ParkWeb\Ase\Transport;

final class RecursionGuard
{
    private bool $active = false;

    public function run(callable $callback): void
    {
        if ($this->active) {
            return;
        }

        $this->active = true;
        try {
            $callback();
        } finally {
            $this->active = false;
        }
    }
}
