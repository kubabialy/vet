<?php

return [
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'],
        'iss' => $_ENV['JWT_ISSUER'],
        'aud' => $_ENV['JWT_AUDIENCE'],
    ],
    'debug' => filter_var($_ENV['DEBUG'] ?? false, FILTER_VALIDATE_BOOL) ?? false,
];
