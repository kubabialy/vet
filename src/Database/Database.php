<?php

declare(strict_types=1);

namespace Vet\Vet\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Database provides a lightweight wrapper for PostgreSQL database operations.
 *
 * This class manages a single PDO connection instance and provides helper methods
 * for executing queries.
 *
 * Configuration is loaded from environment variables:
 * - DB_HOST: Database host (default: 'localhost')
 * - DB_PORT: Database port (default: '5432')
 * - DB_NAME: Database name (required)
 * - DB_USER: Database user (required)
 * - DB_PASS: Database password (required)
 */
class Database
{
    /**
     * Singleton database instance.
     */
    private static ?PDO $instance = null;

    /**
     * Private constructor to enforce singleton pattern.
     *
     * @throws RuntimeException If database connection fails.
     */
    private function __construct()
    {
    }

    /**
     * Gets the singleton PDO database instance.
     *
     * Creates a new connection if one doesn't exist. Connection is established
     * using environment variables for configuration.
     *
     * @return PDO The PDO database instance.
     * @throws RuntimeException If connection fails.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '5432';
            $dbname = getenv('DB_NAME') ?: 'vet';
            $user = getenv('DB_USER') ?: 'postgres';
            $password = getenv('DB_PASS') ?: '';

            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

            try {
                self::$instance = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new RuntimeException("Database connection failed: " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * Executes a query and returns all rows as associative arrays.
     *
     * This method is useful for SELECT queries.
     *
     * @param string $sql The SQL query to execute.
     * @param array $params Optional parameters for prepared statement.
     * @return array Array of associative arrays representing rows.
     * @throws RuntimeException If query execution fails.
     */
    public static function query(string $sql, array $params = []): array
    {
        $pdo = self::getInstance();

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new RuntimeException("Query execution failed: " . $e->getMessage());
        }
    }

    /**
     * Executes a query and returns the first row as an associative array.
     *
     * @param string $sql The SQL query to execute.
     * @param array $params Optional parameters for prepared statement.
     * @return array|null The associative array representing the row, or null if not found.
     */
    public static function queryFirst(string $sql, array $params = []): ?array
    {
        $results = self::query($sql, $params);
        return $results[0] ?? null;
    }

    /**
     * Executes a query and returns the number of affected rows.
     *
     * Useful for INSERT, UPDATE, and DELETE operations.
     *
     * @param string $sql The SQL query to execute.
     * @param array $params Optional parameters for prepared statement.
     * @return int Number of affected rows.
     * @throws RuntimeException If query execution fails.
     */
    public static function execute(string $sql, array $params = []): int
    {
        $pdo = self::getInstance();

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RuntimeException("Query execution failed: " . $e->getMessage());
        }
    }

    /**
     * Gets the last inserted ID.
     *
     * @return string The last inserted ID.
     */
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }

    /**
     * Resets the database instance (useful for testing).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
