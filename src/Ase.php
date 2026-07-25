<?php

namespace ParkWeb\Ase;

use Throwable;

final class Ase
{
    private static ?Client $client = null;

    public static function init(Client $client): void
    {
        self::$client = $client;
    }

    public static function captureException(Throwable $throwable): ?string
    {
        return self::$client?->captureException($throwable);
    }

    public static function captureMessage(string $message, Level $level = Level::Info): ?string
    {
        return self::$client?->captureMessage($message, $level);
    }

    /** @param array<string, mixed> $extra */
    public static function captureTelemetry(string $type, string $message, array $extra = [], Level $level = Level::Info): ?string
    {
        return self::$client?->captureTelemetry($type, $message, $extra, $level);
    }

    /** @param array<string, mixed> $user */
    public static function setUser(array $user): void
    {
        self::$client?->setUser($user);
    }

    public static function setTag(string $key, string $value): void
    {
        self::$client?->setTag($key, $value);
    }

    public static function setExtra(string $key, mixed $value): void
    {
        self::$client?->setExtra($key, $value);
    }

    /** @param array<string, mixed> $breadcrumb */
    public static function addBreadcrumb(array $breadcrumb): void
    {
        self::$client?->addBreadcrumb($breadcrumb);
    }

    public static function withScope(callable $callback): mixed
    {
        return self::$client?->withScope($callback);
    }

    public static function flush(): void
    {
        self::$client?->flush();
    }
}
