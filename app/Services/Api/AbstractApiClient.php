<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Contracts\Client\ClientInterface;
use App\Exceptions\ApiConfigurationException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractApiClient implements ClientInterface
{
    protected LoggerInterface $logger;

    protected string $baseUri;

    protected bool $apiClientVerifySsl;

    public function __construct(
        LoggerInterface $logger,
        string $baseUri = '',
        bool $apiClientVerifySsl = true
    ) {
        if (! $baseUri) {
            throw new ApiConfigurationException('Base URI');
        }

        $this->baseUri = $baseUri;
        $this->logger = $logger;
        $this->apiClientVerifySsl = $apiClientVerifySsl;
    }

    /**
     * @throws GuzzleException
     */
    public function request(string $method, string $relativePath, ?array $body = null, array $headers = []): ResponseInterface
    {
        $client = $this->getBaseClient(array_merge($this->getAuthorizationHeader(), $headers));

        return $client->request(
            $method,
            $relativePath,
            [
                RequestOptions::JSON => $body,
            ]
        );
    }

    protected function getBaseClient(array $headers = []): Client
    {
        $data = [
            'base_uri' => $this->baseUri,
            'verify' => $this->apiClientVerifySsl,
            'headers' => array_merge([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ], $headers),
        ];

        return new Client($data);
    }

    abstract protected function getAuthorizationHeader(): array;
}
