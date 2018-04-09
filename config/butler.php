<?php

return [

    'auth' => [

        'secret_key' => env('BUTLER_AUTH_SECRET_KEY', ''),

        'required_claims' => [
            'aud' => 'https://audience.glesys.com/',
            'iss' => 'https://issuer.glesys.com/',
        ],

    ],

];