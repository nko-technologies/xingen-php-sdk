<?php

declare(strict_types=1);

namespace Xingen\Sdk\Tests\ApiKeys;

use Xingen\Sdk\ApiKeys\CreateApiKeyRequest;
use Xingen\Sdk\Error\NotFoundException;
use Xingen\Sdk\Internal\Json;
use Xingen\Sdk\Tests\Support\LoopbackTestCase;

final class ApiKeysClientIntegrationTest extends LoopbackTestCase
{
    public function testCreateReturnsRawKeyOnce(): void
    {
        $keyId = '3fa85f64-5717-4562-b3fc-2c963f66afa6';
        $body = '{"id":"' . $keyId . '","rawKey":"xgn_test_generated","name":"CI",'
            . '"sandbox":true,"quotaLimit":null,"createdAt":"2026-07-08T00:00:00Z"}';
        $this->server->route('/v1/api-keys', 201, $body);

        $created = $this->client->apiKeys->create(new CreateApiKeyRequest(name: 'CI', sandbox: true));

        $this->assertSame($keyId, $created->id);
        $this->assertSame('xgn_test_generated', $created->rawKey);
        $this->assertTrue($created->sandbox);
        $this->assertNull($created->quotaLimit);

        $request = $this->server->recordedRequestsFor('/v1/api-keys')[0];
        $this->assertSame('Bearer xgn_test_abc123', $request['headers']['AUTHORIZATION']);
        $sent = Json::decode($request['body']);
        $this->assertSame('CI', $sent['name']);
        $this->assertTrue($sent['sandbox']);
    }

    public function testListDeserializesEachKey(): void
    {
        $keyId = '3fa85f64-5717-4562-b3fc-2c963f66afa6';
        $body = '[{"id":"' . $keyId . '","name":"CI","keyPrefix":"xgn_live","sandbox":false,'
            . '"active":true,"quotaLimit":10000,"quotaUsed":42,"lastUsedAt":null,'
            . '"createdAt":"2026-07-01T00:00:00Z","revokedAt":null}]';
        $this->server->route('/v1/api-keys', 200, $body);

        $keys = $this->client->apiKeys->list();

        $this->assertCount(1, $keys);
        $this->assertSame($keyId, $keys[0]->id);
        $this->assertSame(42, $keys[0]->quotaUsed);
        $this->assertTrue($keys[0]->active);
    }

    public function testRevokeSendsDeleteToKeyPath(): void
    {
        $keyId = '3fa85f64-5717-4562-b3fc-2c963f66afa6';
        $this->server->route('/v1/api-keys/' . $keyId, 204, '');

        $this->client->apiKeys->revoke($keyId);

        $request = $this->server->recordedRequestsFor('/v1/api-keys/' . $keyId)[0];
        $this->assertSame('DELETE', $request['method']);
    }

    public function testRevokeUnknownKeyThrowsNotFound(): void
    {
        $keyId = '3fa85f64-5717-4562-b3fc-2c963f66afa7';
        $body = '{"message":"API key not found","error":"NOT_FOUND","code":404,'
            . '"timestamp":"2026-07-08T00:00:00Z"}';
        $this->server->route('/v1/api-keys/' . $keyId, 404, $body);

        $this->expectException(NotFoundException::class);
        $this->client->apiKeys->revoke($keyId);
    }
}
