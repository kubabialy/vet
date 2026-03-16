<?php

declare(strict_types=1);

namespace Vet\Tests;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase as TC;

abstract class TestCase extends TC {
    protected function setUp(): void
    {
        // Load environment variables
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        parent::setUp();
    }
}
