<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;


require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addRoutingMiddleware();


/**
 * This Middleware needs to remain the last one declared. Otherwise it will be unable to
 * catch/handle any errors or exceptions from the middlewares declared after this one.
 */
$errorMiddleware = $app->addErrorMiddleware(true, true, true);


$app->get('/hello/{name}', function (Request $request, Response $response, $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello $name");
    return $response;
});

$app->run();
