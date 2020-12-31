<?php

declare(strict_types=1);

namespace Technodelight\JiraRestApi\Api\IssueApi;

final class IssueCreateMeta
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