<?php

declare(strict_types=1);

namespace Vet\Vet;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Vet\Vet\Handler\UserHandler;


readonly class Routes
{
    public const string POST_REQ = 'post';
    public const string GET_REQ = 'get';
    public const string PUT_REQ = 'put';
    public const string DELETE_REQ = 'delete';
    public const string PATCH_REQ = 'patch';

    /**
     * Defines the application routes and their respective handlers.
     *
     * To add a new route:
     * 1. Add an entry to the array returned by this method.
     * 2. Each entry should be an array with three elements:
     *    - The HTTP method (use one of the constants defined in this class, e.g., self::GET_REQ).
     *    - The route path as a string (e.g., '/example').
     *    - The handler for the route, which can be:
     *      a. A callable function.
     *      b. An array with the class name and method name (e.g., [SomeHandler::class, 'methodName']).
     *         Note: The method should be passed without calling it (e.g., 'methodName', not 'methodName()').
     *
     * Example:
     * [
     *     self::GET_REQ, '/example', function (Request $request, Response $response, $args) {
     *         // Your logic here
     *         return $response;
     *     }
     * ]
     *
     * Or using a class method:
     * [
     *     self::POST_REQ, '/users', [UserHandler::class, 'createUser']
     * ]
     *
     * @return array An array of routes with their HTTP method, path, and handler.
     */
    public static function routes(): array
    {
        return [
            [self::GET_REQ, '/hello/{name}', function (Request $request, Response $response, $args): Response {
                $name = $args['name'];
                $response->getBody()->write("Hello $name");
                return $response;
            }],
            [self::POST_REQ, '/users', [UserHandler::class, 'createUser']]
        ];
    }

}