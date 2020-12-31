<?php

declare(strict_types=1);

namespace Technodelight\JiraRestApi\HttpClient;

interface Config
{
    public function username(): string;
    public function apiKey(): string;
    public function domain(): string;
    public function apiVersion(): string;
}
