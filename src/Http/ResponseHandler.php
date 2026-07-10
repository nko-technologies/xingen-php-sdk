<?php

declare(strict_types=1);

namespace Xingen\Sdk\Http;

use Throwable;
use Xingen\Sdk\Error\ApiException;
use Xingen\Sdk\Error\AuthenticationException;
use Xingen\Sdk\Error\ErrorResponse;
use Xingen\Sdk\Error\NotFoundException;
use Xingen\Sdk\Error\PermissionException;
use Xingen\Sdk\Error\QuotaExceededException;
use Xingen\Sdk\Error\ValidationRequestException;
use Xingen\Sdk\Internal\HttpResponse;
use Xingen\Sdk\Internal\Json;
use Xingen\Sdk\Internal\Wire;

final class ResponseHandler
{
    private function __construct()
    {
    }

    public static function raiseForStatus(HttpResponse $response): void
    {
        if ($response->statusCode >= 200 && $response->statusCode < 300) {
            return;
        }

        throw self::toApiException($response->statusCode, $response->body);
    }

    /** @param callable(mixed): mixed $fromWire */
    public static function decodeOrRaise(HttpResponse $response, callable $fromWire): mixed
    {
        self::raiseForStatus($response);

        return $fromWire(Json::decode($response->body));
    }

    /** @param callable(mixed): mixed $itemFromWire
     * @return list<mixed> */
    public static function decodeListOrRaise(HttpResponse $response, callable $itemFromWire): array
    {
        self::raiseForStatus($response);
        $decoded = Json::decode($response->body);

        return is_array($decoded) ? array_values(array_map($itemFromWire, $decoded)) : [];
    }

    public static function bytesOrRaise(HttpResponse $response): string
    {
        self::raiseForStatus($response);

        return $response->body;
    }

    /** Best-effort parse of the standard error body shape. Never throws: a malformed or
     * unexpected body (e.g. an upstream proxy's HTML error page) must not mask the real HTTP
     * error status with a secondary parse exception. */
    private static function tryParseErrorResponse(string $body): ?ErrorResponse
    {
        if ($body === '') {
            return null;
        }
        try {
            return ErrorResponse::fromWire(Json::decode($body));
        } catch (Throwable) {
            return null;
        }
    }

    /** Best-effort read of one top-level string field, used for the 429 `{"error": "..."}`
     * shape. Never throws, for the same reason as {@see self::tryParseErrorResponse()}. */
    private static function tryExtractField(string $body, string $field): ?string
    {
        if ($body === '') {
            return null;
        }
        try {
            $data = Json::decode($body);
        } catch (Throwable) {
            return null;
        }
        if (!is_array($data)) {
            return null;
        }
        $value = Wire::str($data, $field);

        return $value;
    }

    private static function toApiException(int $statusCode, string $rawBody): ApiException
    {
        if ($statusCode === 429) {
            // Written directly by a security filter, bypassing the standard error pipeline --
            // a minimal {"error": "..."} shape, never the standard ErrorResponse.
            $message = self::tryExtractField($rawBody, 'error') ?? 'Quota exceeded';

            return new QuotaExceededException($message, $rawBody);
        }

        if ($statusCode === 401) {
            // No application-level body exists for this status at all -- don't attempt a parse.
            return new AuthenticationException('Authentication failed — check your API key', $rawBody);
        }

        $errorResponse = self::tryParseErrorResponse($rawBody);
        $message = ($errorResponse?->message !== null && $errorResponse->message !== '')
            ? $errorResponse->message
            : (trim($rawBody) !== '' ? $rawBody : "Request failed with status {$statusCode}");

        return match ($statusCode) {
            403 => new PermissionException($message, $errorResponse, $rawBody),
            404 => new NotFoundException($message, $errorResponse, $rawBody),
            400 => new ValidationRequestException($message, $errorResponse, $rawBody),
            default => new ApiException($message, $statusCode, $errorResponse, $rawBody),
        };
    }
}
