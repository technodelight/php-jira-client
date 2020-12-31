<?php

namespace Technodelight\Jira\Domain;

interface Collection
{
    public static function fromResult(array $result): Collection;

    public function startAt(): int;

    public function maxResults(): int;

    public function total(): int;

    public function isLast(): bool;

    public function items(): array;
}