<?php

declare(strict_types=1);

namespace Vet\Vet;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Vet\Vet\Auth\Auth;
use Vet\Vet\Handler\UserHandler;


readonly class Routes
{
    public const string POST_REQ = 'post';
    public const string GET_REQ = 'get';
    public const string PUT_REQ = 'put';
    public const string DELETE_REQ = 'delete';
    public const string PATCH_REQ = 'patch';

    /**
     * @param array $routes Array of route definitions. Each route should be an array with three elements:
     *                      - HTTP method (use one of the constants, e.g., self::GET_REQ)
     *                      - Route path (e.g., '/example')
     *                      - Handler (callable, [Class::class, 'method'], or [new Object(), 'method'])
     */
    public function __construct(private array $routes)
    {
    }

    /**
     * Applies routes to the given application.
     *
     * Each route entry should be an array with three elements:
     * - The HTTP method (use one of the constants defined in this class, e.g., self::GET_REQ).
     * - The route path as a string (e.g., '/example').
     * - The handler for the route, which can be:
     *   a. A callable function.
     *   b. An array with the class name and method name (e.g., [SomeHandler::class, 'methodName']).
     *   c. An array with an object instance and method name (e.g., [new SomeHandler(), 'methodName']).
     *
     * @param object $app The application instance to register routes on.
     * @throws Exception If the route format is invalid or class/method doesn't exist.
     */
    public function apply(object $app): void
    {
        /**
         * Alternative version of this code, assuming that whoever is using that could be significantly simpler.
         * We could effectively just allow PHP to handle the type checking and explore if the routes aren't set properly.
         *
         * This code could be simplified to:
         *
         * foreach ($this->routes as $route) {
         *     [$method, $path, $handler] = $route;
         *
         *     $app->$method($path, $handler);
         * }
         */
        foreach ($this->routes as $route) {
            if (count($route) !== 3) throw new Exception('Invalid route');

            $handler = $route[2];

            if (is_array($handler)) {
                [$class, $method] = $handler;

                if (is_object($class)) {
                    if (!method_exists($class, $method)) throw new Exception('Invalid method');
                    $handler = [$class, $method];
                } else {
                    if (!class_exists($class)) throw new Exception('Invalid class');
                    if (!method_exists($class, $method)) throw new Exception('Invalid method');
                    $handler = [new $class, $method];
                }
            }

            match ($route[0]) {
                self::GET_REQ => $app->get($route[1], $handler),
                self::POST_REQ => $app->post($route[1], $handler),
                self::PUT_REQ => $app->put($route[1], $handler),
                self::DELETE_REQ => $app->delete($route[1], $handler),
                self::PATCH_REQ => $app->patch($route[1], $handler),
            };
        }
    }

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
     *      c. An array with an object instance and method name (e.g., [new SomeHandler(), 'methodName']).
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
     * Or using an object instance:
     * [
     *     self::POST_REQ, '/users', [new UserHandler(), 'createUser']
     * ]
     *
     * @return self A new Routes instance configured with the application routes.
     */
    public static function initialize(): self
    {
        $jwtSecret = getenv('JWT_SECRET') ?: 'default-secret-key-change-in-production';
        $auth = new Auth($jwtSecret);
        $userHandler = new UserHandler($auth);

        return new self([
            [self::GET_REQ, '/hello/{name}', function (Request $request, Response $response, $args): Response {
                $name = $args['name'];
                $response->getBody()->write("Hello $name");
                return $response;
            }],
            [self::POST_REQ, '/users/signup', [$userHandler, 'signUp']],
            [self::POST_REQ, '/users/signin', [$userHandler, 'signIn']],
        ]);
    }

}