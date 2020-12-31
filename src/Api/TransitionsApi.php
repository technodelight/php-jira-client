<?php

declare(strict_types=1);

namespace Technodelight\JiraRestApi\Api;

use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Domain\Transition;
use Technodelight\JiraRestApi\Client;

class TransitionsApi
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Returns either all transitions or a transition that can be performed by the user on an issue, based on the issue's status.
     * Note, if a request is made for a transition that does not exist or cannot be performed on the issue, given its
     * status, the response will return any empty transitions list.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issues/#api-rest-api-3-issue-issueidorkey-transitions-get
     * @param IssueKey $issueKey
     * @param string|null $transitionId
     * @param bool $skipRemoteOnlyCondition
     * @param bool $includeUnavailableTransitions
     * @param bool $sortByOpsBarAndStatus
     * @return array
     */
    public function listForIssue(IssueKey $issueKey, ?string $transitionId = null, bool $skipRemoteOnlyCondition = false, bool $includeUnavailableTransitions = false, bool $sortByOpsBarAndStatus = false): array
    {
        return array_map(
            static function(array $transition) {
                return Transition::fromArray($transition);
            },
            $this->client->get(
                sprintf('issue/%s/transitions', $issueKey),
                array_filter([
                    'expand' => '*all',
                    'transitionId' => $transitionId,
                    'skipRemoteOnlyCondition' => $skipRemoteOnlyCondition,
                    'includeUnavailableTransitions' => $includeUnavailableTransitions,
                    'sortByOpsBarAndStatus' => $sortByOpsBarAndStatus
                ])
            )['transitions']
        );
    }
}
