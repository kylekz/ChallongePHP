<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Reflex\Challonge\Auth\ApiKeyAuth;
use Reflex\Challonge\Challonge;
use Reflex\Challonge\Exceptions\InvalidFormatException;
use Reflex\Challonge\Exceptions\NotFoundException;
use Reflex\Challonge\Exceptions\ServerException;
use Reflex\Challonge\Exceptions\UnauthorizedException;
use Reflex\Challonge\Exceptions\UnexpectedErrorException;
use Reflex\Challonge\Exceptions\ValidationException;

/**
 * Tests Challonge API v2.1 error handling
 *
 * Verifies that all error responses are properly parsed and appropriate exceptions are thrown
 */
class ErrorHandlingTest extends TestCase
{
    private function createMockClient(int $statusCode, string $json): ClientInterface
    {
        $mock = $this->createMock(ClientInterface::class);
        $mock->method('sendRequest')
            ->willReturn(new Response($statusCode, ['Content-Type' => 'application/json'], $json));

        return $mock;
    }

    private function createChallonge(ClientInterface $client): Challonge
    {
        $auth = new ApiKeyAuth('test_api_key');
        return new Challonge($client, $auth);
    }

    // ==================== 401 UNAUTHORIZED ====================

    public function testThrowsUnauthorizedExceptionOn401(): void
    {
        $errorJson = json_encode([
            'errors' => [
                [
                    'status' => '401',
                    'title' => 'Unauthorized',
                    'detail' => 'Invalid API key provided',
                ],
            ],
        ]);

        $client = $this->createMockClient(401, $errorJson);
        $challonge = $this->createChallonge($client);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Invalid API key provided');

        $challonge->fetchTournament('example_tournament');
    }

    public function testUnauthorizedExceptionWithMissingToken(): void
    {
        $errorJson = json_encode([
            'errors' => [
                [
                    'status' => '401',
                    'title' => 'Unauthorized',
                    'detail' => 'Access token has expired',
                ],
            ],
        ]);

        $client = $this->createMockClient(401, $errorJson);
        $challonge = $this->createChallonge($client);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Access token has expired');

        $challonge->fetchTournament('example_tournament');
    }

    // ==================== 404 NOT FOUND ====================

    public function testThrowsNotFoundExceptionOn404(): void
    {
        $errorJson = json_encode([
            'errors' => [
                [
                    'status' => '404',
                    'title' => 'Not Found',
                    'detail' => 'Tournament not found',
                ],
            ],
        ]);

        $client = $this->createMockClient(404, $errorJson);
        $challonge = $this->createChallonge($client);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Tournament not found');

        $challonge->fetchTournament('nonexistent_tournament');
    }

    public function testNotFoundExceptionWithResourceType(): void
    {
        $errorJson = json_encode([
            'errors' => [
                [
                    'status' => '404',
                    'title' => 'Not Found',
                    'detail' => 'Participant with ID 999999 not found',
                ],
            ],
        ]);

        $client = $this->createMockClient(404, $errorJson);
        $challonge = $this->createChallonge($client);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Participant with ID 999999 not found');

        $challonge->getParticipant('tournament', 999999);
    }

    // ==================== 422 VALIDATION ERROR ====================

    public function testThrowsValidationExceptionOn422(): void
    {
        $errorJson = file_get_contents(__DIR__ . '/Fixtures/ErrorResponse.json');

        $client = $this->createMockClient(422, $errorJson);
        $challonge = $this->createChallonge($client);

        $this->expectException(ValidationException::class);

        $challonge->createTournament([
            'url' => 'test',
            // Missing required 'name' field
        ]);
    }

    public function testValidationExceptionIncludesFieldPointer(): void
    {
        $errorJson = json_encode([
            'errors' => [
                [
                    'status' => '422',
                    'title' => 'Validation Error',
                    'detail' => "can't be blank",
                    'source' => [
                        'pointer' => '/data/attributes/name',
                    ],
                ],
            ],
        ]);

        $client = $this->createMockClient(422, $errorJson);
        $challonge = $this->createChallonge($client);

        try {
            $challonge->createTournament(['url' => 'test']);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('name', $e->getMessage());
            $this->assertStringContainsString("can't be blank", $e->getMessage());

            // Verify error data is preserved
            $errors = $e->getErrors();
            $this->assertIsArray($errors);
            $this->assertArrayHasKey('errors', $errors);
        }
    }

    public function testValidationExceptionWithMultipleErrors(): void
    {
        $errorJson = json_encode([
            'errors' => [
                [
                    'status' => '422',
                    'title' => 'Validation Error',
                    'detail' => "can't be blank",
                    'source' => [
                        'pointer' => '/data/attributes/name',
                    ],
                ],
                [
                    'status' => '422',
                    'title' => 'Validation Error',
                    'detail' => 'has already been taken',
                    'source' => [
                        'pointer' => '/data/attributes/url',
                    ],
                ],
            ],
        ]);

        $client = $this->createMockClient(422, $errorJson);
        $challonge = $this->createChallonge($client);

        try {
            $challonge->createTournament(['url' => 'existing']);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('name', $message);
            $this->assertStringContainsString('url', $message);
            $this->assertStringContainsString("can't be blank", $message);
            $this->assertStringContainsString('has already been taken', $message);
        }
    }

    // ==================== 406 INVALID FORMAT ====================

    public function testThrowsInvalidFormatExceptionOn406(): void
    {
        $errorJson = json_encode([
            'errors' => [
                [
                    'status' => '406',
                    'title' => 'Not Acceptable',
                    'detail' => 'Invalid Accept header. Expected application/json',
                ],
            ],
        ]);

        $client = $this->createMockClient(406, $errorJson);
        $challonge = $this->createChallonge($client);

        $this->expectException(InvalidFormatException::class);
        $this->expectExceptionMessage('Invalid Accept header');

        $challonge->fetchTournament('example_tournament');
    }

    // ==================== 500 SERVER ERROR ====================

    public function testThrowsServerExceptionOn500(): void
    {
        $errorJson = json_encode([
            'errors' => [
                [
                    'status' => '500',
                    'title' => 'Internal Server Error',
                    'detail' => 'An unexpected error occurred',
                ],
            ],
        ]);

        $client = $this->createMockClient(500, $errorJson);
        $challonge = $this->createChallonge($client);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('An unexpected error occurred');

        $challonge->fetchTournament('example_tournament');
    }

    public function testThrowsServerExceptionOn502(): void
    {
        $errorJson = json_encode([
            'errors' => [
                [
                    'status' => '502',
                    'title' => 'Bad Gateway',
                    'detail' => 'Upstream service unavailable',
                ],
            ],
        ]);

        $client = $this->createMockClient(502, $errorJson);
        $challonge = $this->createChallonge($client);

        $this->expectException(ServerException::class);

        $challonge->fetchTournament('example_tournament');
    }

    public function testThrowsServerExceptionOn503(): void
    {
        $errorJson = json_encode([
            'errors' => [
                [
                    'status' => '503',
                    'title' => 'Service Unavailable',
                    'detail' => 'Service is temporarily unavailable',
                ],
            ],
        ]);

        $client = $this->createMockClient(503, $errorJson);
        $challonge = $this->createChallonge($client);

        $this->expectException(ServerException::class);

        $challonge->fetchTournament('example_tournament');
    }

    // ==================== UNEXPECTED ERRORS ====================

    public function testThrowsUnexpectedErrorExceptionForUnknownStatusCode(): void
    {
        $errorJson = json_encode([
            'errors' => [
                [
                    'status' => '418',
                    'title' => "I'm a teapot",
                    'detail' => 'The server refuses to brew coffee',
                ],
            ],
        ]);

        $client = $this->createMockClient(418, $errorJson);
        $challonge = $this->createChallonge($client);

        $this->expectException(UnexpectedErrorException::class);
        $this->expectExceptionMessage("The server refuses to brew coffee");

        $challonge->fetchTournament('example_tournament');
    }

    public function testUnexpectedErrorExceptionPreservesResponseData(): void
    {
        $errorJson = json_encode([
            'errors' => [
                [
                    'status' => '429',
                    'title' => 'Too Many Requests',
                    'detail' => 'Rate limit exceeded',
                    'meta' => [
                        'request_id' => 'req_12345',
                    ],
                ],
            ],
        ]);

        $client = $this->createMockClient(429, $errorJson);
        $challonge = $this->createChallonge($client);

        try {
            $challonge->fetchTournament('example_tournament');
            $this->fail('Expected UnexpectedErrorException to be thrown');
        } catch (UnexpectedErrorException $e) {
            $responseData = $e->getResponse();
            $this->assertIsArray($responseData);
            $this->assertArrayHasKey('errors', $responseData);
        }
    }

    // ==================== MALFORMED RESPONSES ====================

    public function testHandlesEmptyErrorResponse(): void
    {
        $client = $this->createMockClient(500, '');
        $challonge = $this->createChallonge($client);

        $this->expectException(ServerException::class);

        $challonge->fetchTournament('example_tournament');
    }

    public function testHandlesInvalidJsonErrorResponse(): void
    {
        $client = $this->createMockClient(500, 'invalid json {{{');
        $challonge = $this->createChallonge($client);

        $this->expectException(ServerException::class);

        $challonge->fetchTournament('example_tournament');
    }

    public function testHandlesErrorResponseWithoutErrorsArray(): void
    {
        $errorJson = json_encode([
            'message' => 'Something went wrong',
        ]);

        $client = $this->createMockClient(500, $errorJson);
        $challonge = $this->createChallonge($client);

        $this->expectException(ServerException::class);

        $challonge->fetchTournament('example_tournament');
    }

    // ==================== ERROR MESSAGE FORMATTING ====================

    public function testFormatsErrorMessageWithSourcePointer(): void
    {
        $errorJson = json_encode([
            'errors' => [
                [
                    'status' => '422',
                    'detail' => 'must be greater than 0',
                    'source' => [
                        'pointer' => '/data/attributes/signup_cap',
                    ],
                ],
            ],
        ]);

        $client = $this->createMockClient(422, $errorJson);
        $challonge = $this->createChallonge($client);

        try {
            $challonge->createTournament([
                'name' => 'Test',
                'url' => 'test',
                'signup_cap' => -1,
            ]);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('signup_cap', $message);
            $this->assertStringContainsString('must be greater than 0', $message);
        }
    }

    public function testFormatsErrorMessageWithoutSourcePointer(): void
    {
        $errorJson = json_encode([
            'errors' => [
                [
                    'status' => '422',
                    'detail' => 'Tournament has already started',
                ],
            ],
        ]);

        $client = $this->createMockClient(422, $errorJson);
        $challonge = $this->createChallonge($client);

        try {
            $challonge->createTournament(['name' => 'Test', 'url' => 'test']);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Tournament has already started', $e->getMessage());
        }
    }

    // ==================== EXCEPTION HIERARCHY ====================

    public function testAllExceptionsExtendBaseException(): void
    {
        $exceptions = [
            UnauthorizedException::class,
            NotFoundException::class,
            ValidationException::class,
            InvalidFormatException::class,
            ServerException::class,
            UnexpectedErrorException::class,
        ];

        foreach ($exceptions as $exceptionClass) {
            $reflection = new \ReflectionClass($exceptionClass);
            $parent = $reflection->getParentClass();

            // Should extend from \Exception or a base Challonge exception
            $this->assertTrue(
                $parent->getName() === \Exception::class ||
                str_starts_with($parent->getName(), 'Reflex\\Challonge\\Exceptions'),
                "{$exceptionClass} should extend Exception or a Challonge base exception"
            );
        }
    }

    // ==================== HTTP STATUS CODE MAPPING ====================

    public function testHttpStatusCodeToExceptionMapping(): void
    {
        $mapping = [
            401 => UnauthorizedException::class,
            404 => NotFoundException::class,
            406 => InvalidFormatException::class,
            422 => ValidationException::class,
            500 => ServerException::class,
            502 => ServerException::class,
            503 => ServerException::class,
        ];

        foreach ($mapping as $statusCode => $expectedExceptionClass) {
            $errorJson = json_encode([
                'errors' => [
                    [
                        'status' => (string) $statusCode,
                        'title' => 'Test Error',
                        'detail' => "Test error for status {$statusCode}",
                    ],
                ],
            ]);

            $client = $this->createMockClient($statusCode, $errorJson);
            $challonge = $this->createChallonge($client);

            try {
                $challonge->fetchTournament('test');
                $this->fail("Expected {$expectedExceptionClass} for status code {$statusCode}");
            } catch (\Exception $e) {
                $this->assertInstanceOf(
                    $expectedExceptionClass,
                    $e,
                    "Status code {$statusCode} should throw {$expectedExceptionClass}, got " . get_class($e)
                );
            }
        }
    }
}
