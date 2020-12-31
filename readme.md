# PHP Jira Client

Unofficial client for Atlassian Jira, through REST API.

# Usage

```php
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\JiraRestApi\OldApi;
use Technodelight\JiraRestApi\HttpClient;
use Technodelight\JiraRestApi\HttpClient\Config;

class MyConfig implements Config
{    
    public function username() {
        return 'test';    
    }
    public function password() {
        return 'api key from jira instance';
    }
    public function domain() {
        return 'myinstance.atlassian.net';
    }
}
$client = new OldApi(new HttpClient(new MyConfig()));
$issue = $client->retrieveIssue(IssueKey::fromString('TEST-123'));
print $issue->assignee() . PHP_EOL;
```

# License

GNU GPLv3

Copyright (c) 2015-2019 Zsolt Gál
See LICENSE.
