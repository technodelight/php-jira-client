<?php

namespace Technodelight\JiraRestApi;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Promise\Utils;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Technodelight\JiraRestApi\HttpClient\Config;
use UnexpectedValueException;

class HttpClient implements Client
{
    private const API_PATH = '/rest/api/%d/';
    private GuzzleClient $httpClient;
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function post(string $url, array $data = [], array $query = []): array
    {
        try {
            return $this->jsonDecode(
                $this->httpClient()->post(
                    $url,
                    array_filter(['body' => json_encode($data), 'query' => $query])
                )
            );
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    public function put(string $url, array $data = [], array $query = []): array
    {
        try {
            return $this->jsonDecode(
                $this->httpClient()->put(
                    $url,
                    array_filter(['body' => json_encode($data), 'query' => $query])
                )
            );
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    public function get(string $url, array $query = []): array
    {
        try {
            return $this->jsonDecode($this->httpClient()->get($url, array_filter(['query' => $query])));
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    public function delete(string $url, array $query = []): void
    {
        try {
            $this->httpClient()->delete($url, ['query' => $query]);
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    public function multiGet(array $fullUrls): array
    {
        $promises = [];
        foreach ($fullUrls as $url) {
            $promises[$url] = $this->httpClient()->getAsync($url);
        }

        $responses = Utils::settle($promises)->wait();
        $results = [];
        foreach ($responses as $url => $settle) {
            if ($settle['state'] !== 'fulfilled') {
                throw new UnexpectedValueException('Something went wrong while querying JIRA!');
            }
            try {
                $results[$url] = $this->jsonDecode($settle['value']);
            } catch (JsonException $e) {
                $results[$url] = ['exception' => $e];
            }
        }

        return $results;
    }
//
//    /**
//     * @param string $jql
//     * @param string|null $fields
//     *
//     * @return array
//     */
//    public function search($jql, $startAt = null, $fields = null, array $expand = null, array $properties = null)
//    {
//        try {
//            $result = $this->httpClient()->post(
//                'search',
//                [
//                    'json' => array_filter([
//                        'jql' => $jql,
//                        'startAt' => $startAt,
//                        'fields' => (array) $fields,
//                        'expand' => $expand,
//                        'properties' => $properties,
//                    ])
//                ]
//            );
//            return json_decode($result->getBody(), true);
//        } catch (GuzzleClientException $exception) {
//            throw ClientException::fromException($exception);
//        }
//    }

    public function download(string $url, string $targetFilename, ?callable $progressFunction = null): void
    {
        $this->httpClient()->get(
            $url,
            array_filter(['save_to' => $targetFilename, 'progress' => $progressFunction])
        );
    }

    public function upload(string $url, string $sourceFilename): void
    {
        $this->httpClient()->post($url, [
            'headers' => [
                'X-Atlassian-Token' => 'no-check'
            ],
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($sourceFilename, 'rb'),
                    'filename' => pathinfo($sourceFilename, PATHINFO_BASENAME),
                    'headers' => [
                        'X-Atlassian-Token' => 'no-check'
                    ],
                ]
            ]
        ]);
    }

    private function apiUrl(string $projectDomain, int $apiVersion): string
    {
        $parts = parse_url($projectDomain);
        if (count($parts) === 1 && isset($parts['path'])) {
            $parts['host'] = $parts['path'];
            unset($parts['path']);
        }
        $url = implode('', array_filter([
            isset($parts['user'], $parts['pass']) ? $parts['user'] . ':' . $parts['pass'] . '@' : null,
            $parts['host'],
            isset($parts['port']) ? ':' . $parts['port'] : null,
        ]));

        return sprintf(
            '%s://%s%s',
            $parts['proto'] ?? 'https',
            $url,
            sprintf(self::API_PATH, $apiVersion)
        );
    }

    private function httpClient(): GuzzleClient
    {
        if (!isset($this->httpClient)) {
            $this->httpClient = new GuzzleClient(
                [
                    'base_uri' => $this->apiUrl($this->config->domain(), $this->config->apiVersion()),
                    'auth' => [$this->config->username(), $this->config->apiKey()],
                    'allow_redirects' => true,
                ]
            );
        }

        return $this->httpClient;
    }

    public function jsonDecode(ResponseInterface $value): array
    {
        $result = json_decode((string)$value->getBody(), true, null, JSON_THROW_ON_ERROR);
        if (!is_array($result)) {
            throw new UnexpectedValueException('Array expected, got %s', gettype($result));
        }

        return $result;
    }
}
