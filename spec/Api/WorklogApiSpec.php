<?php

namespace spec\Technodelight\JiraRestApi\Api;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\JiraRestApi\Client;

class WorklogApiSpec extends ObjectBehavior
{
    private const RESPONSE_JSON = <<<'JSON'
{
  "self": "https://your-domain.atlassian.net/rest/api/3/issue/10010/worklog/10000",
  "author": {
    "self": "https://your-domain.atlassian.net/rest/api/3/user?accountId=5b10a2844c20165700ede21g",
    "accountId": "5b10a2844c20165700ede21g",
    "displayName": "Mia Krystof",
    "active": false
  },
  "updateAuthor": {
    "self": "https://your-domain.atlassian.net/rest/api/3/user?accountId=5b10a2844c20165700ede21g",
    "accountId": "5b10a2844c20165700ede21g",
    "displayName": "Mia Krystof",
    "active": false
  },
  "comment": {
    "type": "doc",
    "version": 1,
    "content": [
      {
        "type": "paragraph",
        "content": [
          {
            "type": "text",
            "text": "I did some work here."
          }
        ]
      }
    ]
  },
  "updated": "2021-01-25T06:54:07.674+0000",
  "visibility": {
    "type": "group",
    "value": "jira-developers"
  },
  "started": "2021-01-25T06:54:07.674+0000",
  "timeSpent": "3h 20m",
  "timeSpentSeconds": 12000,
  "id": "100028",
  "issueId": "10002"
}
JSON;

    function let(Client $client)
    {
        $this->beConstructedWith($client);
    }

    function it_creates_a_worklog(Client $client, Worklog $worklog)
    {
        $client->post(Argument::type('string'), Argument::type('array'), Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn(json_decode(self::RESPONSE_JSON, true));

        $this->create($worklog);
    }

    function it_updates_a_worklog(Client $client, Worklog $worklog)
    {
        $client->put(Argument::type('string'), Argument::type('array'), Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn(json_decode(self::RESPONSE_JSON, true));

        $this->update($worklog);
    }

    function it_deletes_a_worklog(Client $client, Worklog $worklog)
    {
        $client->delete(Argument::type('string'), Argument::type('array'))->shouldBeCalled();

        $this->delete($worklog);
    }
}
