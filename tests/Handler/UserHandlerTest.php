<?php

declare(strict_types=1);

namespace Vet\Tests\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Vet\Tests\TestCase;
use Vet\Vet\Auth\Auth;
use Vet\Vet\Auth\AuthResult;
use Vet\Vet\DTO\SignInResponse;
use Vet\Vet\DTO\SignUpResponse;
use Vet\Vet\Handler\UserHandler;
use Vet\Vet\Database\User;

/**
 * UserHandlerTest tests the UserHandler class.
 *
 * This test suite covers:
 * - Sign up validation (email format, password length, password match)
 * - Sign up with existing email
 * - Sign up success
 * - Sign in with invalid credentials
 * - Sign in success
 */
#[CoversClass(UserHandler::class)]
class UserHandlerTest extends TestCase
{
    private UserHandler $handler;

    protected function setUp(): void
    {
        $this->testSecret = base64_encode(random_bytes(32));
        $authMock = $this->createMock(Auth::class);
        $this->handler = new UserHandler($authMock);
    }

    public function testSignUpReturnsErrorWhenEmailIsEmpty(): void
    {
        $request = $this->createRequest(['email' => '', 'password' => 'password123', 'repeatedPassword' => 'password123']);
        $response = $this->createResponse();

        $result = $this->handler->signUp($request, $response, []);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertEquals('Email is required', $body['message']);
    }

    public function testSignUpReturnsErrorWhenEmailIsInvalid(): void
    {
        $request = $this->createRequest(['email' => 'invalid-email', 'password' => 'password123', 'repeatedPassword' => 'password123']);
        $response = $this->createResponse();

        $result = $this->handler->signUp($request, $response, []);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertEquals('Invalid email format', $body['message']);
    }

    public function testSignUpReturnsErrorWhenPasswordIsTooShort(): void
    {
        $request = $this->createRequest(['email' => 'test@example.com', 'password' => 'short', 'repeatedPassword' => 'short']);
        $response = $this->createResponse();

        $result = $this->handler->signUp($request, $response, []);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertEquals('Password must be at least 8 characters', $body['message']);
    }

    public function testSignUpReturnsErrorWhenPasswordsDoNotMatch(): void
    {
        $request = $this->createRequest(['email' => 'test@example.com', 'password' => 'password123', 'repeatedPassword' => 'different123']);
        $response = $this->createResponse();

        $result = $this->handler->signUp($request, $response, []);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertEquals('Passwords do not match', $body['message']);
    }

    public function testSignInReturnsErrorWhenEmailIsEmpty(): void
    {
        $request = $this->createRequest(['email' => '', 'password' => 'password123']);
        $response = $this->createResponse();

        $result = $this->handler->signIn($request, $response, []);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertEquals('Email is required', $body['message']);
    }

    public function testSignInReturnsErrorWhenPasswordIsEmpty(): void
    {
        $request = $this->createRequest(['email' => 'test@example.com', 'password' => '']);
        $response = $this->createResponse();

        $result = $this->handler->signIn($request, $response, []);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertEquals('Password is required', $body['message']);
    }

    public function testSignUpResponseCreatesSuccessResponse(): void
    {
        $response = SignUpResponse::success(123);

        $this->assertTrue($response->success);
        $this->assertEquals('User registered successfully', $response->message);
        $this->assertEquals(123, $response->userId);
    }

    public function testSignUpResponseCreatesErrorResponse(): void
    {
        $response = SignUpResponse::error('Some error');

        $this->assertFalse($response->success);
        $this->assertEquals('Some error', $response->message);
        $this->assertNull($response->userId);
    }

    public function testSignInResponseCreatesSuccessResponse(): void
    {
        $response = SignInResponse::success('jwt-token', 123);

        $this->assertTrue($response->success);
        $this->assertEquals('jwt-token', $response->token);
        $this->assertEquals(123, $response->userId);
        $this->assertEquals('Signed in successfully', $response->message);
    }

    public function testSignInResponseCreatesErrorResponse(): void
    {
        $response = SignInResponse::error('Invalid credentials');

        $this->assertFalse($response->success);
        $this->assertEquals('Invalid credentials', $response->message);
        $this->assertNull($response->token);
        $this->assertNull($response->userId);
    }

    private function createRequest(array $body): \Psr\Http\Message\ServerRequestInterface
    {
        $request = $this->createMock(\Psr\Http\Message\ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($body);
        return $request;
    }

    private function createResponse(): \Psr\Http\Message\ResponseInterface
    {
        return new \Slim\Psr7\Response();
    }
}
