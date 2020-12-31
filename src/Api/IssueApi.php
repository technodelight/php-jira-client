<?php

declare(strict_types=1);

namespace Technodelight\JiraRestApi\Api;

use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\JiraRestApi\Api\IssueApi\IssueNotificationData;
use Technodelight\JiraRestApi\Api\IssueApi\IssueUpdateData;
use Technodelight\JiraRestApi\Client;

class IssueApi
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Returns the details for an issue.
     * The issue is identified by its ID or key, however, if the identifier doesn't match an issue,
     * a case-insensitive search and check for moved issues is performed. If a matching issue is found
     * its details are returned, a 302 or other redirect is not returned. The issue key returned in the
     * response is the key of the issue found.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issues/#api-rest-api-3-issue-issueidorkey-get
     * @param IssueKey $issueKey
     * @return Issue
     */
    public function retrieve(IssueKey $issueKey): Issue
    {
        return Issue::fromArray($this->client->get(sprintf('issue/%s', $issueKey)));
    }

    /**
     * Edits the issue from a JSON representation.
     *
     * The fields available for update can be determined using the /rest/api/2/issue/{issueIdOrKey}/editmeta resource.
     * If a field is hidden from the Edit screen then it will not be returned by the editmeta resource. A field
     * validation error will occur if such field is submitted in an edit request. However connect add-on with admin
     * scope may override a screen security configuration.
     * If an issue cannot be edited in Jira because of its workflow status (for example the issue is closed), then
     * you will not be able to edit it with this resource.
     * Field to be updated should appear either in fields or update request’s body parameter, but not in both.
     * To update a single sub-field of a complex field (e.g. timetracking) please use the update parameter of the edit
     * operation. Using a “field_id”: field_value construction in the fields parameter is a shortcut of “set” operation
     * in the update parameter.
     *
     * @param IssueKey $issueKey
     * @param IssueUpdateData $updateData
     * @return Issue
     */
    public function update(IssueKey $issueKey, IssueUpdateData $updateData): Issue
    {
        $this->client->put(sprintf('issue/%s', $issueKey), $updateData->asArray());

        return $this->retrieve($issueKey);
    }

    /**
     * Deletes an issue.
     * An issue cannot be deleted if it has one or more subtasks.
     * To delete an issue with subtasks, set deleteSubtasks. This causes the issue's subtasks to be deleted with the issue.
     *
     * @param IssueKey $issueKey
     */
    public function remove(IssueKey $issueKey): void
    {
        $this->client->delete(sprintf('issue/%s', $issueKey));
    }

    /**
     * Update issue assignee
     *
     * Assigns an issue to a user. Use this operation when the calling user does not have the Edit Issues permission
     * but has the Assign issue permission for the project that the issue is in.
     *
     * Note that:
     * - Only the accountId property needs to be set in the request object.
     * - If accountId in the request object is set to "-1", then the issue is assigned to the default assignee for the project.
     * - If accountId in the request object is set to null, then the issue is set to unassigned.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issues/#api-rest-api-3-issue-issueidorkey-assignee-put
     * @param IssueKey $issueKey
     * @param string $accountId
     */
    public function assign(IssueKey $issueKey, string $accountId): void
    {
        $this->client->put(sprintf('issue/%s/assignee', $issueKey), ['accountId' => $accountId]);
    }

    /**
     * Returns a paginated list of all changelogs for an issue sorted by date, starting from the oldest.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issues/#api-rest-api-3-issue-issueidorkey-assignee-put
     * @param IssueKey $issueKey
     * @param int|null $startAt
     * @param int|null $maxResults
     * @return array
     */
    public function changeLogs(IssueKey $issueKey, ?int $startAt = null, ?int $maxResults = null): array
    {
        return $this->client->get(
            sprintf('issue/%s/changelogs', $issueKey), array_filter(['startAt' => $startAt, 'maxResults' => $maxResults])
        );
    }

    /**
     * Creates an email notification for an issue and adds it to the mail queue.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issues/#api-rest-api-3-issue-issueidorkey-editmeta-get
     * @param IssueKey $issueKey
     * @param IssueNotificationData $notificationData
     */
    public function notify(IssueKey $issueKey, IssueNotificationData $notificationData): void
    {
        $this->client->post(sprintf('issue/%s/notify', $issueKey), $notificationData->asArray());
    }
}
