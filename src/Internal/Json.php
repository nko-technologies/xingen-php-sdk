<?php

declare(strict_types=1);

namespace Xingen\Sdk\Internal;

use RuntimeException;

/**
 * A minimal RFC 8259 JSON parser that preserves every numeric literal as its exact
 * source-text string instead of routing it through an IEEE-754 double.
 *
 * PHP's native json_decode() has no hook equivalent to Python's `parse_float` or a
 * lossless-json-style `parseNumber` callback: floats are always converted through a
 * float64 intermediate, silently corrupting large/exact monetary literals (the same
 * failure mode the Java SDK avoids with BigDecimal, Python with `parse_float=Decimal`,
 * and TypeScript with the `lossless-json` package). Since PHP has no bundled or widely
 * trusted equivalent package, this hand-rolls the same fix as a small dependency-free
 * parser. Numbers decode to plain PHP strings; every other JSON type maps to its normal
 * PHP equivalent (object/array -> associative array, string -> string, true/false ->
 * bool, null -> null). Each model's fromWire() then decides, field by field, whether to
 * keep the string (monetary/quantity fields) or narrow it back to an int (the handful of
 * known-safe small integer counters) via Wire::int()/Wire::intOrNull().
 */
final class Json
{
    public static function encode(mixed $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            throw new RuntimeException('Failed to encode JSON: ' . json_last_error_msg());
        }

        return $encoded;
    }

    public static function decode(string $json): mixed
    {
        $parser = new self($json);
        $value = $parser->parseValue();
        $parser->skipWhitespace();
        if ($parser->pos < $parser->length) {
            throw new RuntimeException("Unexpected trailing data in JSON at offset {$parser->pos}");
        }

        return $value;
    }

    private int $pos = 0;
    private readonly int $length;

    private function __construct(private readonly string $json)
    {
        $this->length = strlen($json);
    }

    private function parseValue(): mixed
    {
        $this->skipWhitespace();
        if ($this->pos >= $this->length) {
            throw new RuntimeException('Unexpected end of JSON input');
        }

        return match ($this->json[$this->pos]) {
            '{' => $this->parseObject(),
            '[' => $this->parseArray(),
            '"' => $this->parseString(),
            't' => $this->parseLiteral('true', true),
            'f' => $this->parseLiteral('false', false),
            'n' => $this->parseLiteral('null', null),
            default => $this->parseNumber(),
        };
    }

    /** @return array<string, mixed> */
    private function parseObject(): array
    {
        $this->pos++; // consume '{'
        $result = [];
        $this->skipWhitespace();
        if ($this->peek() === '}') {
            $this->pos++;

            return $result;
        }
        while (true) {
            $this->skipWhitespace();
            $key = $this->parseString();
            $this->skipWhitespace();
            $this->expect(':');
            $result[$key] = $this->parseValue();
            $this->skipWhitespace();
            $char = $this->peek();
            if ($char === ',') {
                $this->pos++;
                continue;
            }
            if ($char === '}') {
                $this->pos++;
                break;
            }
            throw new RuntimeException("Expected ',' or '}' at offset {$this->pos}");
        }

        return $result;
    }

    /** @return list<mixed> */
    private function parseArray(): array
    {
        $this->pos++; // consume '['
        $result = [];
        $this->skipWhitespace();
        if ($this->peek() === ']') {
            $this->pos++;

            return $result;
        }
        while (true) {
            $result[] = $this->parseValue();
            $this->skipWhitespace();
            $char = $this->peek();
            if ($char === ',') {
                $this->pos++;
                continue;
            }
            if ($char === ']') {
                $this->pos++;
                break;
            }
            throw new RuntimeException("Expected ',' or ']' at offset {$this->pos}");
        }

        return $result;
    }

    private function parseString(): string
    {
        $this->expect('"');
        $start = $this->pos;
        $hasEscapes = false;
        while (true) {
            if ($this->pos >= $this->length) {
                throw new RuntimeException('Unterminated string in JSON');
            }
            $char = $this->json[$this->pos];
            if ($char === '"') {
                break;
            }
            if ($char === '\\') {
                $hasEscapes = true;
                $this->pos += 2;
                continue;
            }
            $this->pos++;
        }
        $raw = substr($this->json, $start, $this->pos - $start);
        $this->pos++; // consume closing quote

        if (!$hasEscapes) {
            return $raw;
        }

        // Escape sequences (\uXXXX, \n, ...) are rare in this SDK's fields and safe to
        // defer to json_decode on just this substring rather than reimplementing them.
        $decoded = json_decode('"' . $raw . '"');
        if (!is_string($decoded)) {
            throw new RuntimeException("Invalid escape sequence in JSON string at offset {$start}");
        }

        return $decoded;
    }

    private function parseNumber(): string
    {
        $start = $this->pos;
        if ($this->peek() === '-') {
            $this->pos++;
        }
        while ($this->pos < $this->length && ctype_digit($this->json[$this->pos])) {
            $this->pos++;
        }
        if ($this->peek() === '.') {
            $this->pos++;
            while ($this->pos < $this->length && ctype_digit($this->json[$this->pos])) {
                $this->pos++;
            }
        }
        $exponent = $this->peek();
        if ($exponent === 'e' || $exponent === 'E') {
            $this->pos++;
            $sign = $this->peek();
            if ($sign === '+' || $sign === '-') {
                $this->pos++;
            }
            while ($this->pos < $this->length && ctype_digit($this->json[$this->pos])) {
                $this->pos++;
            }
        }
        if ($this->pos === $start) {
            throw new RuntimeException("Invalid JSON at offset {$this->pos}");
        }

        return substr($this->json, $start, $this->pos - $start);
    }

    private function parseLiteral(string $literal, mixed $value): mixed
    {
        if (substr($this->json, $this->pos, strlen($literal)) !== $literal) {
            throw new RuntimeException("Invalid JSON literal at offset {$this->pos}");
        }
        $this->pos += strlen($literal);

        return $value;
    }

    private function peek(): ?string
    {
        return $this->pos < $this->length ? $this->json[$this->pos] : null;
    }

    private function expect(string $char): void
    {
        if ($this->peek() !== $char) {
            throw new RuntimeException("Expected '{$char}' at offset {$this->pos}");
        }
        $this->pos++;
    }

    private function skipWhitespace(): void
    {
        while ($this->pos < $this->length && strpbrk($this->json[$this->pos], " \t\n\r") !== false) {
            $this->pos++;
        }
    }
}
