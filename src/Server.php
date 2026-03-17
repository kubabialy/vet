<?php

declare(strict_types=1);

namespace Vet\Vet;

use Dotenv\Dotenv;
use Exception;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Load environment variables from .env file.
 */
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$app = AppFactory::create();

$app->addRoutingMiddleware();


/**
 * This Middleware needs to remain the last one declared. Otherwise, it will be unable to
 * catch/handle any errors or exceptions from the middlewares declared after this one.
 */
$app->addErrorMiddleware(true, true, true);

/**
 * @throws Exception when Routes fail to apply handlers to appropriate dispatchers.
 */
Routes::initialize()->apply($app);


$app->run();
