<?php

namespace spec\Technodelight\JiraRestApi\Api;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Issue\Changelog\Collection;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Domain\Issue\Meta;
use Technodelight\Jira\Domain\Issue\CreateMeta;
use Technodelight\Jira\Domain\Issue\NotificationDetails;
use Technodelight\Jira\Domain\Issue\UpdateData;
use Technodelight\JiraRestApi\Api\WorklogApi;
use Technodelight\JiraRestApi\Client;

class IssueApiSpec extends ObjectBehavior
{
    const ISSUE_ARRAY = [
        'id' => 'DEV-123',
        'self' => 'https://dev.atlassian.net/browse/dev-123',
        'key' => 'DEV-123',
    ];

    function let(Client $client)
    {
        $client->get(Argument::type('string'))->willReturn(self::ISSUE_ARRAY);

        $this->beConstructedWith($client);
    }

    function it_can_retrieve_an_issue(Client $client)
    {
        $this->retrieve(IssueKey::fromString('DEV-123'))->shouldBeAnInstanceOf(Issue::class);
    }

    function it_can_update_an_issue(Client $client)
    {
        $client->put(Argument::type('string'), Argument::type('array'))->shouldBeCalled()->willReturn([]);

        $this->update(IssueKey::fromString('DEV-123'), UpdateData::createEmpty())->shouldBeAnInstanceOf(Issue::class);
    }

    function it_can_assign_to_a_user(Client $client)
    {
        $client->put(Argument::type('string'), Argument::type('array'))->shouldBeCalled();

        $this->assign(IssueKey::fromString('DEV-123'), '5b10ac8d82e05b22cc7d4ef5');
    }

    function it_can_delete_an_issue(Client $client)
    {
        $client->delete(Argument::type('string'))->shouldBeCalled();

        $this->remove(IssueKey::fromString('DEV-123'));
    }

    function it_retrieves_changelogs(Client $client)
    {
        $client->get(Argument::type('string'), [])->shouldBeCalled()->willReturn([
            'self' => 'https://your-domain.atlassian.net/rest/api/3/issue/DEV-123/changelog',
            'startAt' => 0,
            'maxResults' => 5,
            'total' => 5,
            'isLast' => true,
            'values' => []
        ]);

        $this->changelogs(IssueKey::fromString('DEV-123'))->shouldBeAnInstanceOf(Collection::class);
    }

    function it_sends_notifications(Client $client)
    {
        $client->post(Argument::type('string'), Argument::type('array'))->shouldBeCalled();

        $this->notify(IssueKey::fromString('DEV-123'), NotificationDetails::createEmpty());
    }

    function it_gets_create_metadata(Client $client)
    {
        $client->get(Argument::type('string'))->shouldBeCalled()->willReturn([]);

        $this->createMeta()->shouldBeAnInstanceOf(CreateMeta::class);
    }

    function it_gets_edit_metadata(Client $client)
    {
        $client->get(Argument::type('string'), Argument::type('array'))->shouldBeCalled()->willReturn([]);

        $this->editMeta(IssueKey::fromString('DEV-123'))->shouldBeAnInstanceOf(Meta::class);
    }

    function it_deals_with_worklogs()
    {
        $this->worklog()->shouldBeAnInstanceOf(WorklogApi::class);
    }
}
