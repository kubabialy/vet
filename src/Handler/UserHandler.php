<?php

declare(strict_types=1);

namespace Vet\Vet\Handler;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserHandler
{
    public function createUser(Request $request, Response $response, $args): void
    {
        echo "efoo";
    }
}