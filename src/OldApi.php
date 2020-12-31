<?php

declare(strict_types=1);

namespace Technodelight\JiraRestApi;

use BadMethodCallException;
use DateTime;
use ErrorException;
use Generator;
use Sirprize\Queried\QueryException;
use Technodelight\JiraRestApi\SearchQuery\Builder as SearchQueryBuilder;
use Technodelight\Jira\Domain\Comment\CommentId;
use Technodelight\Jira\Domain\Filter\FilterId;
use Technodelight\Jira\Domain\IssueLink\IssueLinkId;
use Technodelight\Jira\Domain\Worklog\WorklogId;
use Technodelight\Jira\Domain\Comment;
use Technodelight\Jira\Domain\Field;
use Technodelight\Jira\Domain\Filter;
use Technodelight\Jira\Domain\Issue\Changelog;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Domain\Issue\Meta;
use Technodelight\Jira\Domain\IssueLink;
use Technodelight\Jira\Domain\IssueLink\Type;
use Technodelight\Jira\Domain\Priority;
use Technodelight\Jira\Domain\Project;
use Technodelight\Jira\Domain\Project\ProjectKey;
use Technodelight\Jira\Domain\Status;
use Technodelight\Jira\Domain\Transition;
use Technodelight\Jira\Domain\UserPickerResult;
use Technodelight\Jira\Domain\User;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueCollection;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Domain\WorklogCollection;

class OldApi
{
    public const FIELDS_ALL = '*all';

    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-user-get
     * @param string|null $accountId
     * @return User
     */
    public function user(?string $accountId = null): User
    {
        if (null === $accountId) {
            return User::fromArray($this->client->get('myself'));
        }

        return User::fromArray(
            $this->client->get(
                'user' . $this->queryStringFromParams([
                    'accountId' => $accountId,
                ])
            )
        );
    }

    /**
     * Returns matching users for a query string in the format of
     *
     * ```
     * {
     * "users": [
     *      {
     *          "name": "fred",
     *          "key": "fred",
     *          "html": "fred@example.com",
     *          "displayName": "Fred Grumble",
     *          "avatarUrl": "http://www.example.com/jira/secure/useravatar?size=small&ownerId=fred"
     *      }
     * ],
     * "total": 25,
     * "header": "Showing 20 of 25 matching groups"
     * }
     * ```
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-user-search/#api-rest-api-3-user-picker-get
     * @param string $query	string A string used to search username, Name or e-mail address
     * @param int|null $maxResults the maximum number of users to return (defaults to 50). The maximum allowed value is 1000. If you specify a value that is higher than this number, your search results will be truncated.
     * @param bool|null $showAvatar boolean
     * @param string|null $exclude string
     * @return UserPickerResult[]
     */
    public function userPicker($query, ?int $maxResults = null, ?bool $showAvatar = null, ?string $exclude = null): array
    {
        $response = $this->client->get(
            'user/picker' . $this->queryStringFromParams([
                'query' => $query,
                'maxResults' => $maxResults,
                'showAvatar' => $showAvatar ? 'true' : 'false',
                'exclude' => $exclude,
            ])
        );
        return array_map(
            static function (array $user) {
                return UserPickerResult::fromArray($user);
            },
            $response['users']
        );
    }

    /**
     * Returns the project details for a project.
     * This operation can be accessed anonymously.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-projects/#api-rest-api-3-project-projectidorkey-get
     * @param ProjectKey $projectKey
     * @return Project
     */
    public function project(ProjectKey $projectKey): Project
    {
        return Project::fromArray($this->client->get(sprintf('project/%s', $projectKey)));
    }

    /**
     * Return list of projects
     * $recent returns the most recent x amount
     *
     * @param  int|null $numberOfRecent
     *
     * @deprecated see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-projects/#api-rest-api-3-project-search-get
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-projects/#api-rest-api-3-project-get
     * @return Project[]
     */
    public function projects(?int $numberOfRecent = null): array
    {
        return array_map(
            static function(array $project) {
                return Project::fromArray($project);
            },
            $this->client->get('project' . $this->queryStringFromParams(['recent' => $numberOfRecent ?: null]))
        );
    }

    /**
     * Return available statuses for a project per issue type
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-projects/#api-rest-api-3-project-projectidorkey-statuses-get
     * @param ProjectKey $projectKey
     * @return Status[]
     */
    public function projectStatuses(ProjectKey $projectKey): array
    {
        $response = $this->client->get(sprintf('project/%s/statuses', $projectKey));
        $statuses = [];
        foreach (array_keys($response) as $k) {
            foreach ($k['statuses'] as $status) {
                $statuses[] = Status::fromArray($status);
            }
        }

        return $statuses;
    }

    /**
     * Return all available statuses across the current instance
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-workflow-statuses/#api-rest-api-3-status-get
     * @return Status[]
     */
    public function workflowStatuses(): array
    {
        return array_map(
            function (array $status) {
                return Status::fromArray($status);
            },
            $this->client->get('status')
        );
    }

    /**
     * Log work against ticket
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-worklogs/#api-rest-api-3-issue-issueidorkey-worklog-post
     * @param Worklog $worklog
     * @return Worklog
     */
    public function createWorklog(Worklog $worklog): Worklog
    {
        $jiraRecord = $this->client->post(
            sprintf('issue/%s/worklog', $worklog->issueIdentifier()) . $this->queryStringFromParams(['adjustEstimate' => 'auto']),
            [
                'comment' => $worklog->comment(),
                'started' => DateHelper::dateTimeToJira($worklog->date()),
                'timeSpentSeconds' => $worklog->timeSpentSeconds(),
            ]
        );
        return Worklog::fromArray($this->normaliseDateFields($jiraRecord), $worklog->issueIdentifier());
    }

    /**
     * Returns worklog details for a list of worklog IDs.
     * The returned list of worklogs is limited to 1000 items.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-worklogs/#api-rest-api-3-worklog-list-post
     * @param WorklogId[] $worklogIds
     * @return Generator
     */
    public function retrieveWorklogs(array $worklogIds): Generator
    {
        $records = $this->client->post(
            'worklog/list?expand=properties',
            [
                'ids' => array_map(function (WorklogId $worklogId) {
                    return (string) $worklogId;
                }, $worklogIds)
            ]
        );

        foreach ($records as $logRecord) {
            yield Worklog::fromArray($this->normaliseDateFields($logRecord), $logRecord['issueId']);
        }
    }

    /**
     * Updates a worklog.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-worklogs/#api-rest-api-3-issue-issueidorkey-worklog-id-put
     * @param Worklog $worklog
     * @return Worklog
     */
    public function updateWorklog(Worklog $worklog): Worklog
    {
        $jiraRecord = $this->client->put(
            sprintf('issue/%s/worklog/%d?adjustEstimate=auto', $worklog->issueIdentifier(), (string) $worklog->id()),
            [
                'comment' => $worklog->comment(),
                'started' => DateHelper::dateTimeToJira($worklog->date()),
                'timeSpentSeconds' => $worklog->timeSpentSeconds(),
            ]
        );
        return Worklog::fromArray($this->normaliseDateFields($jiraRecord), $jiraRecord['issueId']);
    }

    /**
     * Deletes a worklog from an issue.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-worklogs/#api-rest-api-3-issue-issueidorkey-worklog-id-delete
     * @param Worklog $worklog
     */
    public function deleteWorklog(Worklog $worklog): void
    {
        $this->client->delete(sprintf('issue/%s/worklog/%d?adjustEstimate=auto', $worklog->issueKey() ?: $worklog->issueId(), (string) $worklog->id()));
    }

    /**
     * Returns worklogs for an issue, starting from the oldest worklog or from the worklog started on or after a date and time.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-worklogs/#api-rest-api-3-issue-issueidorkey-worklog-get
     * @param IssueKey $issueKey
     * @param int|null $limit
     * @return WorklogCollection
     */
    public function retrieveIssueWorklogs(IssueKey $issueKey, ?int $limit = null): WorklogCollection
    {
        try {
            $response = $this->client->get(sprintf('issue/%s/worklog' . ($limit ? '?maxResults='.$limit : ''), $issueKey));
            $results = WorklogCollection::createEmpty();
            if (!is_null($limit)) {
                $response['worklogs'] = array_slice($response['worklogs'], $limit * -1);
            }
            foreach ($response['worklogs'] as $jiraRecord) {
                $results->push(Worklog::fromArray($this->normaliseDateFields($jiraRecord), $issueKey));
            }

            return $results;
        } catch (\Exception $exception) {
            return WorklogCollection::createEmpty();
        }
    }

    /**
     * Mass fetch worklogs for an issue collection
     *
     * @param IssueCollection $issues
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param User|null $user
     * @param int|null $limit
     */
    private function fetchAndAssignWorklogsToIssues(IssueCollection $issues, ?DateTime $from = null, ?DateTime $to = null, ?User $user = null, ?int $limit = null): void
    {
        $requests = [];
        foreach ($issues->keys() as $issueKey) {
            $requests[] = sprintf('issue/%s/worklog' . ($limit ? '?maxResults='.$limit : ''), $issueKey);
        }

        $responses = $this->client->multiGet($requests);
        foreach ($responses as $requestUrl => $response) {
            list ( ,$issueKey, ) = explode('/', $requestUrl, 3);
            $issue = $issues->find($issueKey);
            foreach ($response['worklogs'] as $k => $log) {
                $response['worklogs'][$k] = $this->normaliseDateFields($log);
            }
            $worklogs = WorklogCollection::fromIssueArray($issue, $response['worklogs']);
            if ($from && $to) {
                $worklogs = $worklogs->filterByDate($from, $to);
            }
            if ($user) {
                $worklogs = $worklogs->filterByUser($user);
            }
            if ($limit) {
                $worklogs = $worklogs->filterByLimit($limit);
            }
            $issue->assignWorklogs($worklogs);
        }
    }

    /**
     * Find issues with matching worklogs for user
     *
     * @param DateTime $from
     * @param DateTime $to
     * @param User|null $user
     * @param int|null $limit
     *
     * @return IssueCollection
     */
    public function findUserIssuesWithWorklogs(DateTime $from, DateTime $to, ?User $user = null, ?int $limit = null): IssueCollection
    {
        $query = SearchQueryBuilder::factory()
            ->worklogDate($from->format('Y-m-d'), $to->format('Y-m-d'));
        if ($user) {
            $query->worklogAuthor($user);
        }

        $issues = $this->search($query->assemble(), null, 'issueKey');
        $this->fetchAndAssignWorklogsToIssues($issues, $from, $to, $user, $limit);

        return $issues;
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
    public function retrieveIssue(IssueKey $issueKey): Issue
    {
        return Issue::fromArray(
            $this->normaliseIssueArray($this->client->get(sprintf('issue/%s', $issueKey)))
        );
    }

    /**
     * @param IssueKey[]|string[] $issueKeys
     * @param array $fields
     * @return IssueCollection
     * @throws QueryException
     *@see OldApi::search()
     */
    public function retrieveIssuesByKey(array $issueKeys, array $fields = [self::FIELDS_ALL]): IssueCollection
    {
        $query = SearchQueryBuilder::factory()
            ->issueKey($issueKeys);
        $result = IssueCollection::createEmpty();
        $startAt = 0;
        do {
            $issues = $this->search($query->assemble(), $startAt, $fields);
            $result->merge($issues);
            if (!$issues->isLast()) {
                $startAt+= 50;
            }
        } while (!$issues->isLast());

        return $result;
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
     * @param array $data
     * @param array $params
     * @return array
     * @see OldApi::issueEditMeta()
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issues/#api-rest-api-3-issue-issueidorkey-put
     *
     */
    public function updateIssue(IssueKey $issueKey, array $data, array $params = [])
    {
        return $this->client->put(sprintf('issue/%s', $issueKey) . $this->queryStringFromParams($params), $data);
    }

    /**
     * Update issue assignee
     *
     * Assigns an issue to a user. Use this operation when the calling user does not have the Edit Issues permission
     * but has the Assign issue permission for the project that the issue is in.
     *
     * Note that:
     * - Only the name property needs to be set in the request object.
     * - If name in the request object is set to "-1", then the issue is assigned to the default assignee for the project.
     * - If name in the request object is set to null, then the issue is set to unassigned.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issues/#api-rest-api-3-issue-issueidorkey-assignee-put
     *
     * @param IssueKey $issueKey
     * @param string|int|null $usernameKey
     */
    public function assignIssue(IssueKey $issueKey, $usernameKey): void
    {
        $this->client->put(sprintf('issue/%s/assignee', $issueKey), ['name' => $usernameKey]);
    }

    /**
     * Returns the keys of all properties for the issue identified by the key or by the id.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-properties/#api-rest-api-3-issue-issueidorkey-properties-get
     * @param IssueKey $issueKey
     * @return array
     */
    public function issueProperties(IssueKey $issueKey): array
    {
        return $this->client->get(sprintf('issue/%s/properties', $issueKey));
    }

    /**
     * Returns the metadata for editing an issue.
     * The fields returned by editmeta resource are the ones shown on the issue’s Edit screen. Fields hidden from the
     * screen will not be returned unless `overrideScreenSecurity` parameter is set to true.
     * If an issue cannot be edited in Jira because of its workflow status (for example the issue is closed), then no
     * fields will be returned, unless `overrideEditableFlag` is set to true.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issues/#api-rest-api-3-issue-issueidorkey-editmeta-get
     * @param IssueKey $issueKey
     * @param bool|null $screenSecurity overrideScreenSecurity
     * @param bool|null $editableFlag overrideEditableFlag
     * @return Meta
     */
    public function issueEditMeta(IssueKey $issueKey, ?bool $screenSecurity = null, ?bool $editableFlag = null): Meta
    {
        $result = $this->client->get(
            sprintf('issue/%s/editmeta', $issueKey) . $this->queryStringFromParams([
                'overrideScreenSecurity' => $screenSecurity,
                'overrideEditableFlag' => $editableFlag,
            ])
        );
        return Meta::fromArrayAndIssueKey($result['fields'], $issueKey);
    }

    /**
     * Returns a paginated list of all updates of an issue, sorted by date, starting from the oldest.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issues/#api-rest-api-3-issue-issueidorkey-changelog-get
     * @param IssueKey $issueKey
     * @param null|int $startAt
     * @param null|int $maxResults
     * @return Changelog[]
     */
    public function issueChangelogs(IssueKey $issueKey, ?int $startAt = null, ?int $maxResults = null): array
    {
        $result = $this->client->get(
            sprintf('issue/%s/changelog', $issueKey) . $this->queryStringFromParams([
                'startAt' => $startAt,
                'maxResults' => $maxResults,
            ])
        );
        $self = $this;
        return array_map(function(array $changelog) use ($self, $issueKey) {
            return Changelog::fromArray($self->normaliseDateFields($changelog), $issueKey);
        }, $result['values']);
    }

    /**
     * Performs an autocomplete with an autocompleteable field using issue meta
     *
     * @param Meta $meta
     * @param string $field
     * @param string $query
     * @return array
     * @todo check if this can be replaced with another functionality, as the autocomplete url is from issue edit meta
     */
    public function autocomplete(Meta $meta, string $field, string $query): array
    {
        return $this->client->get($meta->field($field)->autocompleteUrl() . $query);
    }

    /**
     * Returns either all transitions or a transition that can be performed by the user on an issue, based on the issue's status.
     * Note, if a request is made for a transition that does not exist or cannot be performed on the issue, given its status, the response will return any empty transitions list.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issues/#api-rest-api-3-issue-issueidorkey-transitions-get
     * @param IssueKey $issueKey
     * @return Transition[]
     */
    public function retrievePossibleTransitionsForIssue(IssueKey $issueKey): array
    {
        $result = $this->client->get(sprintf('issue/%s/transitions', $issueKey));
        if (isset($result['transitions'])) {
            return array_map(
                static function(array $transition) {
                    return Transition::fromArray($transition);
                },
                $result['transitions']
            );
        }

        return [];
    }

    /**
     * Performs an issue transition and, if the transition has a screen, updates the fields from the transition screen. Optionally, issue properties can be set.
     * To update the fields on the transition screen, specify the fields in the fields or update parameters in the request body. Get details about the fields by calling fields by Get transition and using the transitions.fields expand.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issues/#api-rest-api-3-issue-issueidorkey-transitions-post
     * @param IssueKey $issueKey
     * @param Transition $transition
     * @return array
     */
    public function performIssueTransition(IssueKey $issueKey, Transition $transition)
    {
        return $this->client
            ->post(
                sprintf('issue/%s/transitions', $issueKey),
                [
                    'transition' => ['id' => $transition->id()]
                ]
            );
    }

    /**
     * Search for issues using jql
     *
     * The fields param (which can be specified multiple times) gives a comma-separated list of fields to include in the response.
     * This can be used to retrieve a subset of fields. A particular field can be excluded by prefixing it with a minus.
     * By default, only navigable (*navigable) fields are returned in this search resource. Note: the default is different
     * in the get-issue resource – the default there all fields (*all).
     *
     * Properties: The properties param is similar to fields and specifies a comma-separated list of issue properties to include.
     * Unlike fields, properties are not included by default. It is also not allowed to request all properties. The number of
     * different properties that may be requested in one query is limited to 5.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/#api-api-2-search-post
     * @param string $jql a JQL query string
     * @param int|null $startAt
     * @param array|string|null $fields the list of fields to return for each issue. By default, all navigable fields are returned.
     * @param array|null $expand A list of the parameters to expand.
     * @param array|null $properties the list of properties to return for each issue. By default no properties are returned.
     * @return IssueCollection
     */
    public function search($jql, $startAt = null, $fields = null, array $expand = null, array $properties = []): IssueCollection
    {
        try {
            $results = $this->client->search($jql, $startAt, $fields, $expand, $properties);
            foreach ($results['issues'] as $k => $issueArray) {
                $results['issues'][$k] = $this->normaliseIssueArray($issueArray);
            }

            return IssueCollection::fromSearchArray($results);
        } catch (\Exception $e) {
            throw new BadMethodCallException(
                $e->getMessage() . PHP_EOL
                . 'See advanced search help at https://confluence.atlassian.com/jiracorecloud/advanced-searching-765593707.html' . PHP_EOL
                . 'Query was "' . $jql . '"',
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Returns an issue priority.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-priorities/#api-rest-api-3-priority-id-get
     * @param string $priorityId
     * @return Priority
     */
    public function priority(string $priorityId): Priority
    {
        return Priority::fromArray($this->client->get(sprintf('priority/%d', $priorityId)));
    }

    /**
     * Download URL to target filename
     *
     * @param string $url
     * @param string $filename
     * @param callable|null $progressFunction
     */
    public function download(string $url, string $filename, ?callable $progressFunction = null): void
    {
        $this->client->download($url, $filename, $progressFunction);
    }

    /**
     * Upload an attachment to an issue
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-attachments/#api-rest-api-3-issue-issueidorkey-attachments-post
     * @param IssueKey $issueKey
     * @param string $attachmentFilePath
     */
    public function addAttachment(IssueKey $issueKey, string $attachmentFilePath): void
    {
        $this->client->upload(
            sprintf('issue/%s/attachments', $issueKey),
            $attachmentFilePath
        );
    }

    /**
     * Adds a comment to an issue.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-comments/#api-rest-api-3-issue-issueidorkey-comment-post
     * @param IssueKey $issueKey
     * @param string $commentString
     * @return Comment
     */
    public function addComment(IssueKey $issueKey, string $commentString): Comment
    {
        $response = $this->client->post(
            sprintf('issue/%s/comment', $issueKey),
            [
                'body' => $commentString
            ]
        );
        return Comment::fromArray($this->normaliseDateFields($response));
    }

    /**
     * Retrieve single comment
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-comments/#api-rest-api-3-issue-issueidorkey-comment-id-get
     * @param IssueKey $issueKey
     * @param CommentId $commentId
     * @return Comment
     */
    public function retrieveComment(IssueKey $issueKey, CommentId $commentId): Comment
    {
        $response = $this->client->get(
            sprintf('issue/%s/comment/%s', $issueKey, $commentId)
        );
        return Comment::fromArray($this->normaliseDateFields($response));
    }

    /**
     * Updates a comment.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-comments/#api-rest-api-3-issue-issueidorkey-comment-id-put
     * @param IssueKey $issueKey
     * @param CommentId $commentId
     * @param string $comment
     * @return Comment
     */
    public function updateComment(IssueKey $issueKey, CommentId $commentId, string $comment): Comment
    {
        $response = $this->client->put(
            sprintf('issue/%s/comment/%s', $issueKey, $commentId),
            [
                'body' => $comment
            ]
        );
        return Comment::fromArray($this->normaliseDateFields($response));
    }

    /**
     * Deletes a comment.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-comments/#api-rest-api-3-issue-issueidorkey-comment-id-delete
     * @param IssueKey $issueKey
     * @param CommentId $commentId
     */
    public function deleteComment(IssueKey $issueKey, CommentId $commentId): void
    {
        $this->client->delete(sprintf('issue/%s/comment/%s', $issueKey, $commentId));
    }

    /**
     * Returns lists of issues matching a query string. Use this resource to provide auto-completion suggestions when the user is looking for an issue using a word or string.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-search/#api-rest-api-3-issue-picker-get
     * @param string|null $query	Query used to filter issue search results.
     * @param string|null $currentJql JQL defining search context. Only issues matching this JQL query are included in the results.
     * @param string|null $currentIssueKey Key of the issue defining search context. The issue defining a context is excluded from the search results.
     * @param string|null $currentProjectId ID of a project defining search context. Only issues belonging to a given project are suggested.
     * @param bool|null $showSubTasks Set to false to exclude subtasks from the suggestions list.
     * @param bool|null $showSubTaskParent Set to false to exclude parent issue from the suggestions list if search is performed in the context of a sub-task.
     * @return IssueCollection
     * @throws ErrorException|QueryException if sections is missing from picker response
     */
    public function issuePicker(
        ?string $query = null,
        ?string $currentJql = null,
        ?string $currentIssueKey = null,
        ?string $currentProjectId = null,
        ?bool $showSubTasks = null,
        ?bool $showSubTaskParent = null
    ): IssueCollection {
        $response = $this->client->get(
            'issue/picker' . $this->queryStringFromParams([
                'query' => $query,
                'currentJQL' => $currentJql,
                'currentIssueKey' => $currentIssueKey,
                'currentProjectId' => $currentProjectId,
                'showSubTasks' => $showSubTasks,
                'showSubTaskParent' => $showSubTaskParent
            ])
        );
        if (empty($response['sections'])) {
            throw new ErrorException(
                '"sections" is missing from response'
            );
        }
        $issueKeys = [];
        foreach ($response['sections'] as $section) {
            foreach($section['issues'] as $pickedIssue) {
                $issueKeys[] = $pickedIssue['key'];
            }
        }

        return $this->retrieveIssuesByKey($issueKeys);
    }

    /**
     * Return all available issue fields
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-fields/#api-rest-api-3-field-get
     * @return Field[]
     */
    public function fields()
    {
        return array_map(
            static function (array $field) {
                return Field::fromArray($field);
            },
            $this->client->get('field')
        );
    }

    /**
     * Creates a link between two issues. Use this operation to indicate a relationship between two issues and optionally
     * add a comment to the from (outward) issue. To use this resource the site must have Issue Linking enabled.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-links/#api-rest-api-3-issuelink-post
     * @param IssueKey $inwardIssueKey
     * @param IssueKey $outwardIssueKey
     * @param string $linkName
     * @param string $comment
     * @return IssueLink
     */
    public function linkIssue(
        IssueKey $inwardIssueKey,
        IssueKey $outwardIssueKey,
        string $linkName,
        string $comment = ''
    ): IssueLink {
        $data = [
            'type' => ['name' => $linkName],
            'inwardIssue' => ['key' => (string) $inwardIssueKey],
            'outwardIssue' => ['key' => (string) $outwardIssueKey],
            'comment' => !empty($comment) ? ['body' => $comment] : false,
        ];

        $this->client->post('issueLink', array_filter($data));

        return IssueLink::fromArray($data);
    }

    /**
     * Returns an issue link.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-links/#api-rest-api-3-issuelink-linkid-get
     * @param IssueLinkId $linkId
     * @return IssueLink
     */
    public function retrieveIssueLink(IssueLinkId $linkId): IssueLink
    {
        return IssueLink::fromArray($this->client->get(sprintf('issueLink/%s', $linkId)));
    }

    /**
     * Deletes an issue link with the specified id. To be able to delete an issue link
     * you must be able to view both issues and must have the link issue permission for
     * at least one of the issues.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/#api-api-2-issueLink-linkId-delete
     *
     * @param IssueLinkId $linkId
     * @return bool
     */
    public function removeIssueLink(IssueLinkId $linkId)
    {
        $this->client->delete(sprintf('issueLink/%s', $linkId));
        return true;
    }

    /**
     * Returns a list of available issue link types, if issue linking is enabled.
     * Each issue link type has an id, a name and a label for the outward and inward link relationship.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-link-types/#api-rest-api-3-issuelinktype-get
     * @return Type[]
     */
    public function linkTypes(): array
    {
        return array_map(
            static function(array $linkType) { return Type::fromArray($linkType); },
            $this->client->get('issueLinkType')['issueLinkTypes']
        );
    }

    /**
     * Returns all filters for the current user
     *
     * @deprecated
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-filters/#api-rest-api-3-filter-get
     * @return Filter[]
     */
    public function retrieveFilters(): array
    {
        return array_map(
            static function (array $filter) {
                return Filter::fromArray($filter);
            },
            $this->client->get('filter')
        );
    }

    /**
     * Returns a filter given an id
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-filters/#api-rest-api-3-filter-id-get
     * @param FilterId $filterId
     * @return Filter
     */
    public function retrieveFilter(FilterId $filterId): Filter
    {
        return Filter::fromArray($this->client->get(sprintf('filter/%s', $filterId)));
    }

    private function normaliseIssueArray(array $jiraIssue): array
    {
        $attachments = isset($jiraIssue['fields']['attachment']) ? $jiraIssue['fields']['attachment'] : [];
        foreach ($attachments as $k => $attachment) {
            $attachments[$k] = $this->normaliseDateFields($attachment);
        }
        $jiraIssue['fields']['attachment'] = $attachments;
        $parent = !empty($jiraIssue['parent']) ? $jiraIssue['parent'] : null;
        if ($parent) {
            $jiraIssue['fields']['parent'] = $this->normaliseDateFields($parent);
        }
        $comments = isset($jiraIssue['fields']['comment']) ? $jiraIssue['fields']['comment'] : [];
        if ($comments) {
            foreach ($comments['comments'] as $k => $comment) {
                $comments['comments'][$k] = $this->normaliseDateFields($comment);
            }
        }
        $jiraIssue['fields']['comment'] = $comments;
        $worklog = isset($jiraIssue['fields']['worklog']) ? $jiraIssue['fields']['worklog'] : [];
        if ($worklog) {
            foreach ($worklog['worklogs'] as $k => $comment) {
                $worklog['worklogs'][$k] = $this->normaliseDateFields($comment);
            }
        }
        $jiraIssue['fields']['worklog'] = $worklog;
        $jiraIssue['fields'] = $this->normaliseDateFields($jiraIssue['fields']);

        return $jiraIssue;
    }

    private function normaliseDateFields(array $jiraItem): array
    {
        $fields = ['created', 'started', 'updated', 'createdAt', 'startedAt', 'updatedAt'];
        foreach ($fields as $field) {
            if (isset($jiraItem[$field])) {
                $jiraItem[$field] = $this->normaliseDate($jiraItem[$field]);
            }
        }
        return $jiraItem;
    }

    private function normaliseDate($jiraDate): string
    {
        return DateHelper::dateTimeFromJira($jiraDate)->format(DateHelper::FORMAT_FROM_JIRA);
    }


    private function queryStringFromParams(array $query): string
    {
        $params = http_build_query(array_filter($query, function($value) { return !is_null($value); }));
        return $params ? '?' . $params : '';
    }
}
