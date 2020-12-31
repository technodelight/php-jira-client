<?php

namespace spec\Technodelight\JiraRestApi;

use PhpSpec\ObjectBehavior;
use Technodelight\JiraRestApi\Api\IssueApi;
use Technodelight\JiraRestApi\Api\TransitionsApi;
use Technodelight\JiraRestApi\Client;

class ApiSpec extends ObjectBehavior
{
    function let(Client $client)
    {
        $this->beConstructedWith($client);
    }
    function it_has_an_issue_api(Client $client)
    {
        $this->issue()->shouldBeAnInstanceOf(IssueApi::class);
    }

    function it_has_a_transitions_api(Client $client)
    {
        $this->transitions()->shouldBeAnInstanceOf(TransitionsApi::class);
    }
}
