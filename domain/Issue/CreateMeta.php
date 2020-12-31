<?php

declare(strict_types=1);

namespace Technodelight\Jira\Domain\Issue;

final class CreateMeta
{
    public static function createEmpty(): self
    {
        return new self;
    }

    public static function fromArray(array $data): self
    {
        $instance = new self;

        return $instance;
    }

    private function __construct()
    {
    }
}