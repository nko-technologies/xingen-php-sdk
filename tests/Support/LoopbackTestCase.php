<?php

declare(strict_types=1);

namespace Xingen\Sdk\Tests\Support;

use PHPUnit\Framework\TestCase;
use Xingen\Sdk\XingenClient;

abstract class LoopbackTestCase extends TestCase
{
    protected LoopbackServer $server;
    protected XingenClient $client;

    protected function setUp(): void
    {
        $this->server = new LoopbackServer();
        $this->client = new XingenClient(apiKey: 'xgn_test_abc123', baseUrl: $this->server->baseUrl);
    }

    protected function tearDown(): void
    {
        $this->server->stop();
    }
}
