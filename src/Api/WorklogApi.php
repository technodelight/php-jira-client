<?php

declare(strict_types=1);

namespace Technodelight\JiraRestApi\Api;

use Technodelight\Jira\Domain\Worklog;
use Technodelight\JiraRestApi\Client;
use Technodelight\JiraRestApi\DateHelper;

class WorklogApi
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Adds a worklog to an issue.
     * Time tracking must be enabled in Jira, otherwise this operation returns an error. For more information, see Configuring time tracking.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-worklogs/#api-rest-api-3-issue-issueidorkey-worklog-post
     * @param Worklog $worklog
     * @return Worklog
     */
    public function create(Worklog $worklog): Worklog
    {
        $result = $this->client->post(
            sprintf('issue/%s/worklog', $worklog->issueIdentifier()),
            [
                'comment' => $worklog->comment(),
                'started' => DateHelper::dateTimeToJira($worklog->date()),
                'timeSpentSeconds' => $worklog->timeSpentSeconds(),
            ],
            [
                'adjustEstimate' => 'auto'
            ]
        );

        return Worklog::fromArray(DateHelper::normaliseDateFields($result), $worklog->issueIdentifier());
    }
}