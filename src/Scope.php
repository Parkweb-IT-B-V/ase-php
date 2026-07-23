<?php

namespace ParkWeb\Ase;

final class Scope
{
    /** @var array<string, mixed> */
    private array $user = [];

    /** @var array<string, string> */
    private array $tags = [];

    /** @var array<string, mixed> */
    private array $extra = [];

    /** @var array<int, array<string, mixed>> */
    private array $breadcrumbs = [];

    /** @param array<string, mixed> $user */
    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    public function setTag(string $key, string $value): void
    {
        $this->tags[$key] = $value;
    }

    public function setExtra(string $key, mixed $value): void
    {
        $this->extra[$key] = $value;
    }

    /** @param array<string, mixed> $breadcrumb */
    public function addBreadcrumb(array $breadcrumb): void
    {
        $this->breadcrumbs[] = ['timestamp' => gmdate(DATE_ATOM)] + $breadcrumb;
        $this->breadcrumbs = array_slice($this->breadcrumbs, -100);
    }

    public function clone(): self
    {
        $scope = new self;
        $scope->user = $this->user;
        $scope->tags = $this->tags;
        $scope->extra = $this->extra;
        $scope->breadcrumbs = $this->breadcrumbs;

        return $scope;
    }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return [
            'user' => $this->user ?: null,
            'tags' => $this->tags,
            'extra' => $this->extra,
            'breadcrumbs' => $this->breadcrumbs,
        ];
    }
}
