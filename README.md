+![.github/workflows/test.yml](https://github.com/glesys/butler-auth/workflows/.github/workflows/test.yml/badge.svg)


# Butler Auth

## Installation

```sh
composer require glesys/butler-auth

php artisan butler-auth:generate-secret-key
```

## Configuration

Add the `config/butler.php` configuration file if not already present in your
project. All Butler Auth configuration is located under the `auth` key.

**Options**

- _[required]_ `secret_key` – The secret key used to sign and verify tokens.
- _[required]_ `required_claims` – An array with the claims required for token validation.

**Example**

```php
<?php

return [

    'auth' => [

        'secret_key' => env('BUTLER_AUTH_SECRET_KEY', ''),

        'required_claims' => [
            'aud' => 'audience.glesys.com',
            'iss' => 'issuer.glesys.com',
        ],

    ],

    // ...

];
```

Update your `config/auth.php` configuration file to use the `jwt` guard driver.

```php
<?php

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'api'),
    ],

    'guards' => [
        'api' => [
            'driver' => 'jwt',
        ],
    ],

];
```

Generate tokens with `php artisan butler-auth:generate-token`.

Enable your `auth` middleware and you're good to go!
