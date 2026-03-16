<?php

return [
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'],
        'iss' => $_ENV['JWT_ISSUER'],
        'aud' => $_ENV['JWT_AUDIENCE'],
    ],
    'debug' => (bool) $_ENV['DEBUG'] ?? false,
];
