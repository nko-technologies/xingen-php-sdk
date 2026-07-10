<?php

declare(strict_types=1);

namespace Xingen\Sdk;

use InvalidArgumentException;
use Xingen\Sdk\ApiKeys\ApiKeysClient;
use Xingen\Sdk\Internal\HttpClient;
use Xingen\Sdk\Invoices\InvoicesClient;

/**
 * Entry point for the Xingen SDK.
 *
 * ```php
 * $client = new XingenClient(apiKey: getenv('XINGEN_API_KEY'));
 * $client->invoices->get('inv_123');
 * $client->apiKeys->list();
 * ```
 *
 * Holds one connection-pooled curl handle -- construct it once and reuse it, don't rebuild
 * it per request.
 *
 * No automatic retries are performed anywhere: retrying a submit() after a client-side
 * timeout is unsafe without idempotency keys. Handle retries at the call site if you need them.
 */
final class XingenClient
{
    private const DEFAULT_BASE_URL = 'https://app.xingen.de/api';
    private const DEFAULT_CONNECT_TIMEOUT = 10.0;
    private const DEFAULT_REQUEST_TIMEOUT = 30.0;

    public readonly InvoicesClient $invoices;
    public readonly ApiKeysClient $apiKeys;

    public function __construct(
        string $apiKey,
        string $baseUrl = self::DEFAULT_BASE_URL,
        float $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT,
        float $requestTimeout = self::DEFAULT_REQUEST_TIMEOUT,
    ) {
        if (trim($apiKey) === '') {
            throw new InvalidArgumentException('apiKey must not be blank');
        }

        $http = new HttpClient($baseUrl, $apiKey, $connectTimeout, $requestTimeout);
        $this->invoices = new InvoicesClient($http);
        $this->apiKeys = new ApiKeysClient($http);
    }
}
