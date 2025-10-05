<?php
return [
    'csrf' => [
        'token_name' => '_csrf',
        'lifetime' => 7200,
    ],
    'rate_limit' => [
        'login' => ['max' => 5, 'window' => 300],
    ],
    'csp' => "default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self' 'unsafe-inline';",
];
