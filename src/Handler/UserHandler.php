<?php

declare(strict_types=1);

namespace Vet\Vet\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Vet\Vet\Auth\Auth;
use Vet\Vet\Config\Config;
use Vet\Vet\Database\Database;
use Vet\Vet\Database\User;
use Vet\Vet\DTO\SignInResponse;
use Vet\Vet\DTO\SignUpResponse;
use Vet\Vet\DTO\UserStatus;

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
     * @param ServerRequestInterface $request The HTTP request.
     * @param ResponseInterface $response The HTTP response.
     * @param array $args Route arguments (unused).
     * @return ResponseInterface The HTTP response with JSON body.
     */
    public static function signUp(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
    ): ResponseInterface
    {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return self::jsonResponse($response, SignUpResponse::error('Invalid request body'), 400);
        }

        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';
        $repeatedPassword = $body['repeatedPassword'] ?? '';

        $validationError = self::validateSignUpData($email, $password, $repeatedPassword);
        if ($validationError !== null) {
            return self::jsonResponse($response, SignUpResponse::error($validationError), 400);
        }

        if (self::emailExists($email)) {
            return self::jsonResponse($response, SignUpResponse::error('Email already registered'), 409);
        }

        $userId = self::createUser(
            $email,
            password_hash($password, PASSWORD_ARGON2ID),
            UserStatus::ACTIVE,
        );

        return self::jsonResponse($response, SignUpResponse::success($userId), 201);
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
     * @param ServerRequestInterface $request The HTTP request.
     * @param ResponseInterface $response The HTTP response.
     * @param array $args Route arguments (unused).
     * @return ResponseInterface The HTTP response with JSON body.
     */
    public static function signIn(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
    ): ResponseInterface
    {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return self::jsonResponse($response, SignInResponse::error('Invalid request body'), 400);
        }

        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (empty($email)) {
            return self::jsonResponse($response, SignInResponse::error('Email is required'), 400);
        }

        if (empty($password)) {
            return self::jsonResponse($response, SignInResponse::error('Password is required'), 400);
        }

        $user = self::findUserByEmail($email);

        if ($user === null) {
            return self::jsonResponse($response, SignInResponse::error('Invalid credentials'), 401);
        }

        $config = Config::getInstance()->config;

        $authResult = Auth::authenticate(
            $user,
            $password,
            $config['jwt']['secret'],
            $config['jwt']['iss'],
            $config['jwt']['aud'],
        );

        return $authResult->hasError
            ? self::jsonResponse($response, SignInResponse::error($authResult->error), 401)
            : self::jsonResponse($response, SignInResponse::success($authResult->token, $user->id), 200);
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
    private static function validateSignUpData(string $email, string $password, string $repeatedPassword): ?string
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
    private static function emailExists(string $email): bool
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
    private static function createUser(string $email, string $hashedPassword, string $status): int
    {
        $sql = '
            INSERT INTO users (email, hashed_password, status, created_at, updated_at)
            VALUES (:email, :hashedPassword, :status, NOW(), NOW())
            RETURNING id
        ';

        $result = Database::query($sql, [
            'email' => $email,
            'hashedPassword' => $hashedPassword,
            'status' => $status
        ]);

        if (empty($result)) {
            throw new RuntimeException('Failed to create user');
        }

        return (int)$result[0]['id'];
    }

    /**
     * Finds a user by email address.
     *
     * @param string $email The email to search for.
     * @return User|null The user object if found, null otherwise.
     */
    private static function findUserByEmail(string $email): ?User
    {
        $sql = 'SELECT * FROM users WHERE email = :email LIMIT 1';
        $row = Database::queryFirst($sql, ['email' => $email]);

        if ($row === null) {
            return null;
        }

        return new User(
            id: (int)$row['id'],
            email: $row['email'],
            hashedPassword: $row['hashed_password'],
            status: $row['status'],
            banReason: $row['ban_reason'],
            disableReason: $row['disable_reason'],
        );
    }

    /**
     * Creates a JSON response.
     *
     * @param ResponseInterface $response The HTTP response.
     * @param object $data The data to encode as JSON.
     * @param int $statusCode The HTTP status code.
     * @return ResponseInterface The JSON response.
     */
    private static function jsonResponse(ResponseInterface $response, object $data, int $statusCode): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
