<?php

declare(strict_types=1);

namespace Technodelight\JiraRestApi;

interface Client
{
    public function post(string $url, array $data = [], array $query = []): array;
    public function put(string $url, array $data = [], array $query = []): array;
    public function get(string $url, array $query = []): array;
    public function delete(string $url, array $query = []): void;
    public function multiGet(array $fullUrls): array;
    public function download(string $url, string $targetFilename, ?callable $progressFunction = null): void;
    public function upload(string $url, string $sourceFilename): void;
}
