<?php

declare(strict_types=1);

namespace Xingen\Sdk\Tests\Internal;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Xingen\Sdk\Internal\Json;

final class JsonTest extends TestCase
{
    public function testPreservesTrailingZerosOnMonetaryLiterals(): void
    {
        $result = Json::decode('{"price": 199.00}');

        $this->assertSame('199.00', $result['price']);
        $this->assertIsString($result['price']);
    }

    public function testPreservesFullPrecisionForValuesBeyondFloat64ExactRange(): void
    {
        $result = Json::decode('{"amount": 123456789123456789.123456789}');

        $this->assertSame('123456789123456789.123456789', $result['amount']);
    }

    public function testPreservesSmallIntegersAsExactStringsToo(): void
    {
        $result = Json::decode('{"quantity": 5}');

        $this->assertSame('5', $result['quantity']);
    }

    public function testLeavesStringsBooleansNullAndNestingUntouched(): void
    {
        $result = Json::decode(
            '{"name": "Acme", "active": true, "missing": null, "nested": {"list": [1, 2.50]}}',
        );

        $this->assertSame('Acme', $result['name']);
        $this->assertTrue($result['active']);
        $this->assertNull($result['missing']);
        $this->assertSame(['1', '2.50'], $result['nested']['list']);
    }

    public function testThrowsOnMalformedJson(): void
    {
        $this->expectException(RuntimeException::class);
        Json::decode('not even json {{{');
    }

    public function testHandlesEscapedCharactersInStrings(): void
    {
        $result = Json::decode('{"note": "line1\\nline2 \\"quoted\\" \\u00e9"}');

        $this->assertSame("line1\nline2 \"quoted\" \u{e9}", $result['note']);
    }

    public function testEncodeRoundTripsThroughDecode(): void
    {
        $encoded = Json::encode(['a' => 1, 'b' => 'x/y', 'c' => null]);
        $decoded = Json::decode($encoded);

        $this->assertSame('1', $decoded['a']);
        $this->assertSame('x/y', $decoded['b']);
        $this->assertNull($decoded['c']);
    }
}
