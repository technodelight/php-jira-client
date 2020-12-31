<?php

namespace spec\Technodelight\JiraRestApi\Api;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\JiraRestApi\Api\IssueApi;
use Technodelight\JiraRestApi\Api\IssueApi\IssueNotificationData;
use Technodelight\JiraRestApi\Api\IssueApi\IssueUpdateData;
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

        $this->update(IssueKey::fromString('DEV-123'), IssueUpdateData::createEmpty())->shouldBeAnInstanceOf(Issue::class);
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
        $client->get(Argument::type('string'), [])->shouldBeCalled()->willReturn([]);

        $this->changeLogs(IssueKey::fromString('DEV-123'));
    }

    function it_sends_notifications(Client $client)
    {
        $client->post(Argument::type('string'), Argument::type('array'))->shouldBeCalled();

        $this->notify(IssueKey::fromString('DEV-123'), IssueNotificationData::createEmpty());
    }
}