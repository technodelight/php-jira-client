<?php

namespace spec\Technodelight\JiraRestApi\Api;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\JiraRestApi\Api\TransitionsApi;
use Technodelight\JiraRestApi\Client;

class TransitionsApiSpec extends ObjectBehavior
{
    function let(Client $client)
    {
        $this->beConstructedWith($client);
    }

    function it_has_a_list_of_transitions_for_an_issue(Client $client)
    {
        $client->get(Argument::type('string'), Argument::type('array'))->shouldBeCalled()->willReturn(['transitions' => []]);

        $this->listForIssue(IssueKey::fromString('DEV-123'))->shouldBeArray();
    }
}
