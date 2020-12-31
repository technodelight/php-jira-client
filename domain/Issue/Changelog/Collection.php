<?php

declare(strict_types=1);

namespace Technodelight\Jira\Domain\Issue\Changelog;

use Technodelight\Jira\Domain\Collection as JiraCollection;
use Technodelight\Jira\Domain\Issue\Changelog;
use Technodelight\Jira\Domain\Issue\IssueKey;
use UnexpectedValueException;

final class Collection implements JiraCollection
{
    private int $startAt;
    private int $maxResults;
    private int $total;
    private bool $isLast;
    /** @var Changelog[] */
    private array $changeLogs;

    public static function fromResult(array $result): self
    {
        $instance = new self;
        $instance->startAt = $result['startAt'];
        $instance->maxResults = $result['maxResults'];
        $instance->total = $result['total'];
        $instance->isLast = $result['isLast'];
        $issueKey = IssueKey::fromString(self::extractIssueKeyFromSelf($result['self']));
        $instance->changeLogs = array_map(
            static function(array $changeLog) use ($issueKey) {
                return Changelog::fromArray($changeLog, $issueKey);
            },
            $result['values']
        );

        return $instance;
    }

    private static function extractIssueKeyFromSelf(string $uri): string
    {
        // "self": "https://your-domain.atlassian.net/rest/api/3/issue/TT-1/changelog?startAt=2&maxResults=2",
        $path = explode('/', parse_url($uri, PHP_URL_PATH));
        if ('changelog' === array_pop($path)) {
            return array_pop($path);
        }

        throw new UnexpectedValueException('Unable to get IssueKey from ' . $uri);
    }

    public function startAt(): int
    {
        return $this->startAt;
    }

    public function maxResults(): int
    {
        return $this->maxResults;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function isLast(): bool
    {
        return $this->isLast;
    }

    /** @return Changelog[] */
    public function items(): array
    {
        return $this->changeLogs;
    }
}