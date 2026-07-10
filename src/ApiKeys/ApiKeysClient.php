<?php

declare(strict_types=1);

namespace Xingen\Sdk\ApiKeys;

use Xingen\Sdk\Http\ResponseHandler;
use Xingen\Sdk\Internal\HttpClient;
use Xingen\Sdk\Internal\Json;

final class ApiKeysClient
{
    private const BASE_PATH = '/v1/api-keys';

    public function __construct(private readonly HttpClient $http)
    {
    }

    public function create(CreateApiKeyRequest $request): CreatedApiKey
    {
        $response = $this->http->request('POST', self::BASE_PATH, jsonBody: Json::encode($request->toArray()));

        return ResponseHandler::decodeOrRaise($response, CreatedApiKey::fromWire(...));
    }

    /** @return list<ApiKey> */
    public function list(): array
    {
        $response = $this->http->request('GET', self::BASE_PATH);

        return ResponseHandler::decodeListOrRaise($response, ApiKey::fromWire(...));
    }

    public function revoke(string $keyId): void
    {
        $response = $this->http->request('DELETE', self::BASE_PATH . '/' . $keyId);
        ResponseHandler::raiseForStatus($response);
    }
}
