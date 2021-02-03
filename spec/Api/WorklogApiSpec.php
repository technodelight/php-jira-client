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
  "self": "<string>",
  "author": {
    "self": "<string>",
    "name": "<string>",
    "key": "<string>",
    "accountId": "<string>",
    "emailAddress": "<string>",
    "avatarUrls": {
      "16x16": "<string>",
      "24x24": "<string>",
      "32x32": "<string>",
      "48x48": "<string>"
    },
    "displayName": "<string>",
    "active": true,
    "timeZone": "<string>",
    "accountType": "<string>"
  },
  "updateAuthor": {
    "self": "<string>",
    "name": "<string>",
    "key": "<string>",
    "accountId": "<string>",
    "emailAddress": "<string>",
    "avatarUrls": {
      "16x16": "<string>",
      "24x24": "<string>",
      "32x32": "<string>",
      "48x48": "<string>"
    },
    "displayName": "<string>",
    "active": true,
    "timeZone": "<string>",
    "accountType": "<string>"
  },
  "created": "2021-01-25T06:54:07.674+0000",
  "updated": "2021-01-25T06:54:07.674+0000",
  "visibility": {
    "type": "group",
    "value": "<string>"
  },
  "started": "2021-01-25T06:54:07.674+0000",
  "timeSpent": "<string>",
  "timeSpentSeconds": 192,
  "id": "<string>",
  "issueId": "<string>",
  "properties": [
    {
      "key": "<string>"
    }
  ]
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
}
