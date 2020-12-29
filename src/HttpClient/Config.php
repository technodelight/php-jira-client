<?php

namespace Technodelight\JiraRestApi\HttpClient;

interface Config
{
    public function username();

    public function password();

    public function domain();
}
