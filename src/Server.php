<?php

declare(strict_types=1);

namespace Vet\Vet;

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Vet\Vet\Routes;


$app = AppFactory::create();

$app->addRoutingMiddleware();


/**
 * This Middleware needs to remain the last one declared. Otherwise, it will be unable to
 * catch/handle any errors or exceptions from the middlewares declared after this one.
 */
$app->addErrorMiddleware(true, true, true);

foreach (Routes::routes() as $route) {
    match ($route[0]) {
        Routes::GET_REQ => $app->get($route[1], $route[2]),
        Routes::POST_REQ => $app->post($route[1], $route[2]),
        Routes::PUT_REQ => $app->put($route[1], $route[2]),
        Routes::DELETE_REQ => $app->delete($route[1], $route[2]),
        Routes::PATCH_REQ => $app->patch($route[1], $route[2]),
    };
}


$app->run();
