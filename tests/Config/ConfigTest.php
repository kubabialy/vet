<?php

namespace Vet\Tests\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use Vet\Tests\TestCase;
use Vet\Vet\Config\Config;

#[CoversClass(Config::class)]
class ConfigTest extends TestCase
{
    public function testItWorks(): void
    {
        $previousDebugEnv = $_ENV['DEBUG'] ?? null;
        $_ENV['DEBUG'] = '1';

        try {
            $config = Config::getInstance();
            $this->assertIsArray($config->config);
            $this->assertTrue($config->config['debug']);
        } finally {
            if ($previousDebugEnv === null) {
                unset($_ENV['DEBUG']);
            } else {
                $_ENV['DEBUG'] = $previousDebugEnv;
            }
        }
    }
}