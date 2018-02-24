# Butler Auth

## Installation

```sh
composer require glesys/butler-auth
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

        'secret_key' => 'ff7c899a2108b262226e58314ee28850',

        'required_claims' => [
            'aud' => 'https://audience.glesys.com/',
            'iss' => 'https://issuer.glesys.com/',
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

Enable your `auth` middleware and you're good to go!
