<?php

declare(strict_types=1);

namespace Vet\Tests\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use Vet\Tests\TestCase;
use Vet\Vet\Auth\Auth;
use Vet\Vet\Auth\AuthResult;
use Vet\Vet\Database\User;
use Vet\Vet\DTO\UserStatus;

#[CoversClass(Auth::class)]
class AuthTest extends TestCase
{
    private string $testSecret;

    protected function setUp(): void
    {
        $this->testSecret = base64_encode(random_bytes(32));
    }

    private function createUser(array $data): User
    {
        return new User(
            id: $data['id'],
            email: $data['email'] ?? 'test@example.com',
            hashedPassword: $data['passwordHash'],
            status: $data['status'],
            banReason: $data['banReason'] ?? null,
            disableReason: $data['disableReason'] ?? null,
        );
    }

    public function testAuthenticateReturnsJwtStringOnSuccess(): void
    {
        $user = $this->createUser([
            'id' => 1,
            'passwordHash' => password_hash('correct-password', PASSWORD_ARGON2ID),
            'status' => UserStatus::ACTIVE,
            'banReason' => null,
            'disableReason' => null,
        ]);

        $result = Auth::authenticate($user, 'correct-password', $this->testSecret, 'iss', 'aud');

        $this->assertIsString($result->token);
        $this->assertCount(3, explode('.', $result->token));
    }

    public function testAuthenticateFailsWithWrongPassword(): void
    {
        $user = $this->createUser([
            'id' => 1,
            'passwordHash' => password_hash('correct-password', PASSWORD_ARGON2ID),
            'status' => UserStatus::ACTIVE,
        ]);

        $result = Auth::authenticate($user, 'wrong-password', $this->testSecret, 'iss', 'aud');
        $this->assertTrue($result->hasError);
        $this->assertNull($result->token);
        $this->assertEquals(AuthResult::ERROR_INVALID_CREDENTIALS, $result->error);
    }

    public function testAuthenticateFailsWhenUserIsBanned(): void
    {
        $user = $this->createUser([
            'id' => 1,
            'passwordHash' => password_hash('correct-password', PASSWORD_ARGON2ID),
            'status' => UserStatus::BANNED,
            'banReason' => 'Spam activity',
        ]);

        $result = Auth::authenticate($user, 'correct-password', $this->testSecret, 'iss', 'aud');
        $this->assertTrue($result->hasError);
        $this->assertNull($result->token);
        $this->assertEquals(AuthResult::ERROR_USER_BANNED, $result->error);
    }

    public function testAuthenticateFailsWhenUserIsDisabled(): void
    {
        $user = $this->createUser([
            'id' => 1,
            'passwordHash' => password_hash('correct-password', PASSWORD_ARGON2ID),
            'status' => UserStatus::DISABLED,
            'disableReason' => 'Account under review',
        ]);

        $result = Auth::authenticate($user, 'correct-password', $this->testSecret, 'iss', 'aud');
        $this->assertTrue($result->hasError);
        $this->assertNull($result->token);
        $this->assertEquals(AuthResult::ERROR_USER_DISABLED, $result->error);
    }

    public function testAuthenticateFailsWhenUserIsNotActivated(): void
    {
        $user = $this->createUser([
            'id' => 1,
            'passwordHash' => password_hash('correct-password', PASSWORD_ARGON2ID),
            'status' => UserStatus::INACTIVE,
        ]);

        $result = Auth::authenticate($user, 'correct-password', $this->testSecret, 'iss', 'aud');
        $this->assertTrue($result->hasError);
        $this->assertNull($result->token);
        $this->assertEquals(AuthResult::ERROR_USER_INACTIVE, $result->error);
    }

    public function testAuthenticateFailsWhenUserAccountIsDeleted(): void
    {
        $user = $this->createUser([
            'id' => 1,
            'passwordHash' => password_hash('correct-password', PASSWORD_ARGON2ID),
            'status' => UserStatus::DELETED,
        ]);

        $result = Auth::authenticate($user, 'correct-password', $this->testSecret, 'iss', 'aud');
        $this->assertTrue($result->hasError);
        $this->assertNull($result->token);
        $this->assertEquals(AuthResult::ERROR_USER_DELETED, $result->error);
    }

    public function testAuthenticateFailsWithEmptyPassword(): void
    {
        $user = $this->createUser([
            'id' => 1,
            'passwordHash' => password_hash('some-password', PASSWORD_ARGON2ID),
            'status' => UserStatus::ACTIVE,
        ]);

        $result = Auth::authenticate($user, '', $this->testSecret, 'iss', 'aud');
        $this->assertTrue($result->hasError);
        $this->assertNull($result->token);
        $this->assertEquals(AuthResult::ERROR_PASSWORD_EMPTY, $result->error);
    }

    public function testJwtContainsUserId(): void
    {
        $user = $this->createUser([
            'id' => 123,
            'passwordHash' => password_hash('password', PASSWORD_ARGON2ID),
            'status' => UserStatus::ACTIVE,
        ]);

        $result = Auth::authenticate($user, 'password', $this->testSecret, 'iss', 'aud');

        $payload = json_decode(base64_decode(explode('.', $result->token)[1]), true);
        $this->assertArrayHasKey('sub', $payload);
        $this->assertEquals(123, $payload['sub']);
    }

    public function testJwtContainsIssuedAt(): void
    {
        $user = $this->createUser([
            'id' => 1,
            'passwordHash' => password_hash('password', PASSWORD_ARGON2ID),
            'status' => UserStatus::ACTIVE,
        ]);

        $result = Auth::authenticate($user, 'password', $this->testSecret, 'iss', 'aud');

        $payload = json_decode(base64_decode(explode('.', $result->token)[1]), true);
        $this->assertArrayHasKey('iat', $payload);
    }

    public function testJwtContainsExpiration(): void
    {
        $user = $this->createUser([
            'id' => 1,
            'passwordHash' => password_hash('password', PASSWORD_ARGON2ID),
            'status' => UserStatus::ACTIVE,
        ]);

        $result = Auth::authenticate($user, 'password', $this->testSecret, 'iss', 'aud');

        $payload = json_decode(base64_decode(explode('.', $result->token)[1]), true);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertGreaterThan($payload['iat'], $payload['exp']);
    }
}
