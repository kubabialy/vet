<?php

declare(strict_types=1);

namespace Vet\Vet\Handler;

use RuntimeException;
use Vet\Vet\Auth\Auth;
use Vet\Vet\Database\Database;
use Vet\Vet\Database\User;
use Vet\Vet\DTO\SignInRequest;
use Vet\Vet\DTO\SignInResponse;
use Vet\Vet\DTO\SignUpRequest;
use Vet\Vet\DTO\SignUpResponse;

/**
 * UserHandler handles user-related HTTP requests.
 *
 * This handler is responsible for user registration (signup) and
 * authentication (signin) operations. It uses the Database class
 * for persistence and the Auth class for JWT token generation.
 */
class UserHandler
{
    /**
     * Minimum password length requirement.
     */
    private const int MIN_PASSWORD_LENGTH = 8;

    /**
     * UserHandler constructor.
     *
     * @param Auth $auth Auth instance for JWT token generation.
     */
    public function __construct(
        private readonly Auth $auth,
    ) {}

    /**
     * Handles user registration (sign up) requests.
     *
     * This method validates the incoming request data, checks if the email
     * is already registered, and creates a new user in the database if valid.
     *
     * Request body should contain:
     * - email: User's email address (required)
     * - password: User's password (required, min 8 characters)
     * - repeatedPassword: Password confirmation (required, must match password)
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The HTTP request.
     * @param \Psr\Http\Message\ResponseInterface $response The HTTP response.
     * @param array $args Route arguments (unused).
     * @return \Psr\Http\Message\ResponseInterface The HTTP response with JSON body.
     */
    public function signUp(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface
    {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return $this->jsonResponse($response, SignUpResponse::error('Invalid request body'), 400);
        }

        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';
        $repeatedPassword = $body['repeatedPassword'] ?? '';

        $validationError = $this->validateSignUpData($email, $password, $repeatedPassword);
        if ($validationError !== null) {
            return $this->jsonResponse($response, SignUpResponse::error($validationError), 400);
        }

        if ($this->emailExists($email)) {
            return $this->jsonResponse($response, SignUpResponse::error('Email already registered'), 409);
        }

        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);

        $userId = $this->createUser($email, $hashedPassword);

        return $this->jsonResponse($response, SignUpResponse::success($userId), 201);
    }

    /**
     * Handles user authentication (sign in) requests.
     *
     * This method validates the incoming credentials, finds the user by email,
     * and generates a JWT token if authentication is successful.
     *
     * Request body should contain:
     * - email: User's email address (required)
     * - password: User's password (required)
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The HTTP request.
     * @param \Psr\Http\Message\ResponseInterface $response The HTTP response.
     * @param array $args Route arguments (unused).
     * @return \Psr\Http\Message\ResponseInterface The HTTP response with JSON body.
     */
    public function signIn(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface
    {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return $this->jsonResponse($response, SignInResponse::error('Invalid request body'), 400);
        }

        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (empty($email)) {
            return $this->jsonResponse($response, SignInResponse::error('Email is required'), 400);
        }

        if (empty($password)) {
            return $this->jsonResponse($response, SignInResponse::error('Password is required'), 400);
        }

        $user = $this->findUserByEmail($email);

        if ($user === null) {
            return $this->jsonResponse($response, SignInResponse::error('Invalid credentials'), 401);
        }

        $authResult = $this->auth->authenticate($user, $password);

        if ($authResult->hasError) {
            return $this->jsonResponse($response, SignInResponse::error($authResult->error), 401);
        }

        return $this->jsonResponse($response, SignInResponse::success($authResult->token, $user->id), 200);
    }

    /**
     * Validates sign-up data.
     *
     * Checks email format, password length, and password match.
     *
     * @param string $email User's email address.
     * @param string $password User's password.
     * @param string $repeatedPassword Password confirmation.
     * @return string|null Error message if validation fails, null if valid.
     */
    private function validateSignUpData(string $email, string $password, string $repeatedPassword): ?string
    {
        if (empty($email)) {
            return 'Email is required';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email format';
        }

        if (empty($password)) {
            return 'Password is required';
        }

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return 'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters';
        }

        if ($password !== $repeatedPassword) {
            return 'Passwords do not match';
        }

        return null;
    }

    /**
     * Checks if an email is already registered.
     *
     * @param string $email The email to check.
     * @return bool True if email exists, false otherwise.
     */
    private function emailExists(string $email): bool
    {
        $sql = 'SELECT id FROM users WHERE email = :email LIMIT 1';
        $result = Database::query($sql, ['email' => $email]);
        return !empty($result);
    }

    /**
     * Creates a new user in the database.
     *
     * @param string $email User's email address.
     * @param string $hashedPassword Hashed password.
     * @return int The ID of the newly created user.
     * @throws RuntimeException If user creation fails.
     */
    private function createUser(string $email, string $hashedPassword): int
    {
        $sql = '
            INSERT INTO users (email, hashed_password, is_logable, is_deleted, is_active, created_at, updated_at)
            VALUES (:email, :hashedPassword, true, false, true, NOW(), NOW())
            RETURNING id
        ';

        $result = Database::query($sql, [
            'email' => $email,
            'hashedPassword' => $hashedPassword,
        ]);

        if (empty($result)) {
            throw new RuntimeException('Failed to create user');
        }

        return (int) $result[0]['id'];
    }

    /**
     * Finds a user by email address.
     *
     * @param string $email The email to search for.
     * @return User|null The user object if found, null otherwise.
     */
    private function findUserByEmail(string $email): ?User
    {
        $sql = 'SELECT * FROM users WHERE email = :email LIMIT 1';
        $result = Database::query(
            $sql,
            ['email' => $email],
            User::class,
            [
                'id' => 'id',
                'email' => 'email',
                'hashed_password' => 'hashedPassword',
                'is_logable' => 'isLogable',
                'is_deleted' => 'isDeleted',
                'is_active' => 'isActive',
                'ban_reason' => 'banReason',
                'disable_reason' => 'disableReason',
            ]
        );

        return $result[0] ?? null;
    }

    /**
     * Creates a JSON response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response The HTTP response.
     * @param object $data The data to encode as JSON.
     * @param int $statusCode The HTTP status code.
     * @return \Psr\Http\Message\ResponseInterface The JSON response.
     */
    private function jsonResponse(\Psr\Http\Message\ResponseInterface $response, object $data, int $statusCode): \Psr\Http\Message\ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
