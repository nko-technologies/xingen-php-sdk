<?php

declare(strict_types=1);

namespace Xingen\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Xingen\Sdk\Error\ApiException;
use Xingen\Sdk\Error\AuthenticationException;
use Xingen\Sdk\Error\NotFoundException;
use Xingen\Sdk\Error\PermissionException;
use Xingen\Sdk\Error\QuotaExceededException;
use Xingen\Sdk\Error\ValidationRequestException;
use Xingen\Sdk\Http\ResponseHandler;
use Xingen\Sdk\Internal\HttpResponse;

final class ResponseHandlingTest extends TestCase
{
    public function testSucceedsSilentlyOn2xx(): void
    {
        $this->expectNotToPerformAssertions();
        ResponseHandler::raiseForStatus(new HttpResponse(202, '{"id":"abc"}'));
    }

    public function testMapsQuotaExceededShapeThatDiffersFromErrorResponse(): void
    {
        try {
            ResponseHandler::raiseForStatus(new HttpResponse(429, '{"error":"Quota exceeded"}'));
            $this->fail('expected QuotaExceededException');
        } catch (QuotaExceededException $e) {
            $this->assertSame('Quota exceeded', $e->getMessage());
            $this->assertSame(429, $e->statusCode);
            $this->assertNull($e->errorResponse);
            $this->assertStringContainsString('Quota exceeded', $e->rawBody);
        }
    }

    public function testMapsAuthenticationExceptionWithoutAttemptingErrorResponseParse(): void
    {
        $this->expectException(AuthenticationException::class);
        ResponseHandler::raiseForStatus(new HttpResponse(401, ''));
    }

    public function testMapsAuthenticationExceptionEvenWithUnexpectedHtmlBody(): void
    {
        $this->expectException(AuthenticationException::class);
        ResponseHandler::raiseForStatus(new HttpResponse(401, '<html>not json</html>'));
    }

    public function testMapsForbiddenWithErrorResponseBody(): void
    {
        $body = '{"message":"Invoice exists but is not owned by caller","error":"FORBIDDEN",'
            . '"code":403,"timestamp":"2026-07-08T00:00:00Z"}';
        try {
            ResponseHandler::raiseForStatus(new HttpResponse(403, $body));
            $this->fail('expected PermissionException');
        } catch (PermissionException $e) {
            $this->assertSame('Invoice exists but is not owned by caller', $e->getMessage());
        }
    }

    public function testMapsNotFound(): void
    {
        $body = '{"message":"The requested resource was not found","error":"NOT_FOUND",'
            . '"code":404,"timestamp":"2026-07-08T00:00:00Z"}';
        $this->expectException(NotFoundException::class);
        ResponseHandler::raiseForStatus(new HttpResponse(404, $body));
    }

    public function testMapsBadRequestAndSurfacesFieldErrors(): void
    {
        $body = '{"message":"Validation failed","error":"BAD_REQUEST","code":400,'
            . '"timestamp":"2026-07-08T00:00:00Z","fieldErrors":{"invoiceNumber":"must not be blank"}}';
        try {
            ResponseHandler::raiseForStatus(new HttpResponse(400, $body));
            $this->fail('expected ValidationRequestException');
        } catch (ValidationRequestException $e) {
            $this->assertSame('must not be blank', $e->fieldErrors['invoiceNumber']);
        }
    }

    public function testMapsUnmappedStatusToGenericApiExceptionWithoutThrowingOnMalformedBody(): void
    {
        try {
            ResponseHandler::raiseForStatus(new HttpResponse(500, 'not even json {{{'));
            $this->fail('expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame(500, $e->statusCode);
            $this->assertNull($e->errorResponse);
            $this->assertSame('not even json {{{', $e->rawBody);
        }
    }
}
