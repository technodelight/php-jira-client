<?php

declare(strict_types=1);

namespace Technodelight\JiraRestApi;

use Technodelight\JiraRestApi\Api\IssueApi;
use Technodelight\JiraRestApi\Api\TransitionsApi;

class Api
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function issue(): IssueApi
    {
        return $this->factory(IssueApi::class);
    }

    public function transitions(): TransitionsApi
    {
        return $this->factory(TransitionsApi::class);
    }

    private function factory($className)
    {
        static $instances = [];
        if (!isset($instances[$className])) {
            $instances[$className] = new $className($this->client);
        }
        return $instances[$className];
    }
}
