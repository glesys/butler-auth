<?php

return [

    'auth' => [

        'secret_key' => env('BUTLER_AUTH_SECRET_KEY', ''),

        'required_claims' => [
            'aud' => 'audience.glesys.com',
            'iss' => 'issuer.glesys.com',
        ],

    ],

];
