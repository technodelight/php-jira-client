<?php

declare(strict_types=1);

namespace Technodelight\Jira\Domain\Issue;

final class NotificationDetails
{
    public static function createEmpty(): self
    {
        return new self;
    }

    public function asArray(): array
    {
        return [];
    }
}