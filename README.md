[![Build Status](https://travis-ci.org/glesys/butler-auth.svg?branch=master)](https://travis-ci.org/glesys/butler-auth)

# Butler Auth

## Installation

```sh
composer require glesys/butler-auth
```

## Registration

Register the Butler Auth service provider in `bootstrap/app.php`:

```php
$app->register(Butler\Auth\ServiceProvider::class);
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

Don't forget to set `BUTLER_AUTH_SECRET_KEY` in `.env` or your environment.

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

Enable your `auth` middleware and you're good to go!
