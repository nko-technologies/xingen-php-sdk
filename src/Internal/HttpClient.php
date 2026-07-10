<?php

declare(strict_types=1);

namespace Xingen\Sdk\Internal;

use CurlHandle;
use Xingen\Sdk\Error\XingenIOException;
use Xingen\Sdk\Version;

/**
 * Thin wrapper around a single reused curl handle, carrying the base URL, auth header, and
 * per-request timeout every SDK call needs.
 *
 * Unlike the Java SDK (built on `java.net.http.HttpClient`, which has neither connection
 * pooling built in for this use case nor multipart support) this relies on ext-curl, which
 * PHP has bundled by default: a single curl handle reused across requests keeps its
 * connection cache (keep-alive) automatically, and `CURLStringFile` (PHP >= 8.1) lets a
 * multipart file part be built from an in-memory byte string without touching disk, so no
 * separate transport/request-builder/multipart-encoder classes are needed here.
 */
final class HttpClient
{
    private readonly string $baseUrl;
    private readonly CurlHandle $handle;
    private readonly string $userAgent;

    public function __construct(
        string $baseUrl,
        private readonly string $apiKey,
        private readonly float $connectTimeout,
        private readonly float $requestTimeout,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->handle = curl_init();
        $this->userAgent = self::buildUserAgent();
    }

    public function __destruct()
    {
        curl_close($this->handle);
    }

    /**
     * @param array<string, scalar|null>|null $query null-valued entries are dropped entirely
     *   (not sent as empty strings), matching the other language SDKs' behavior
     * @param array<string, string> $headers
     */
    public function request(
        string $method,
        string $path,
        ?array $query = null,
        ?string $jsonBody = null,
        ?FileUpload $file = null,
        array $headers = [],
    ): HttpResponse {
        $url = $this->buildUrl($path, $query);

        $allHeaders = array_merge([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
            'User-Agent' => $this->userAgent,
        ], $headers);

        curl_setopt_array($this->handle, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => (int) round($this->connectTimeout * 1000),
            CURLOPT_TIMEOUT_MS => (int) round($this->requestTimeout * 1000),
            CURLOPT_HEADER => false,
        ]);

        if ($file !== null) {
            // Deliberately no explicit Content-Type header here -- libcurl computes the
            // multipart boundary and sets the header itself; an explicit value would break it.
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, [
                'file' => new \CURLStringFile($file->content, $file->filename, $file->contentType),
            ]);
        } elseif ($jsonBody !== null) {
            $allHeaders['Content-Type'] ??= 'application/json';
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, $jsonBody);
        } else {
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, null);
        }

        curl_setopt($this->handle, CURLOPT_HTTPHEADER, self::flattenHeaders($allHeaders));

        $body = curl_exec($this->handle);
        if ($body === false) {
            throw new XingenIOException("Request to {$url} failed: " . curl_error($this->handle));
        }

        $statusCode = (int) curl_getinfo($this->handle, CURLINFO_RESPONSE_CODE);

        return new HttpResponse($statusCode, (string) $body);
    }

    /** @param array<string, scalar|null>|null $query */
    private function buildUrl(string $path, ?array $query): string
    {
        $url = $this->baseUrl . ($path[0] === '/' ? $path : '/' . $path);
        if ($query === null) {
            return $url;
        }

        $params = [];
        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }
            $params[$key] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }
        if ($params === []) {
            return $url;
        }

        return $url . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /** @param array<string, string> $headers
     * @return list<string> */
    private static function flattenHeaders(array $headers): array
    {
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = "{$name}: {$value}";
        }

        return $lines;
    }

    private static function buildUserAgent(): string
    {
        return sprintf('xingen-php-sdk/%s (PHP/%s)', Version::VERSION, PHP_VERSION);
    }
}
