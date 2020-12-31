<?php

declare(strict_types=1);

namespace Technodelight\JiraRestApi\Api\IssueApi;

final class IssueUpdateData
{
    public static function createEmpty(): self
    {
        return new self;
    }

    public function asArray(): array
    {
        return [];
    }

    private function __construct()
    {
    }
}