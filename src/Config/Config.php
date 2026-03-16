<?php

namespace Vet\Vet\Config;

class Config
{
    public readonly array $config;

    public static self $instance;

    private function __construct()
    {
        $this->config = require __DIR__ . '/../../config/config.php';
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }
}