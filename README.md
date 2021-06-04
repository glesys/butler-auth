![.github/workflows/test.yml](https://github.com/glesys/butler-auth/workflows/.github/workflows/test.yml/badge.svg)


# Butler Auth

A minimal token authentication package inspired by [Laravel Sanctum](https://laravel.com/docs/8.x/sanctum).

## Installation

Requires a working Laravel app with a database connection, a queue worker and a cache store like redis or memcached.

```sh
composer require glesys/butler-auth

php artisan vendor:publish --tag=butler-auth-migrations
php artisan migrate
```

## Generate token

1. Implement `Butler\Auth\Models\Contracts\HasAccessTokens` on your consumer model.
2. Use `Butler\Auth\Models\Concerns\HasAccessTokens` on your consumer model.

```php
$token = $consumer->createToken(abilities: ['*'], name: 'my token')->plainTextToken;
```

## Deleting tokens

Because of the caching that occurres when fetching access tokens, it is important
to delete tokens through the model and **not** in the database directly.

```php
// Delete access token by ID
AccessToken::find($id)->delete();

// Delete all tokens for a consumer
$consumer->tokens()->delete();
```

## Protecting Routes

See [Laravels documentation](https://laravel.com/docs/8.x/authentication#protecting-routes).

```php
// If "butler" is configured as your default guard
Route::view('/protected', 'protected')->middleware('auth');

// Or specify the guard
Route::view('/protected', 'protected')->middleware('auth:butler');
```

## Authenticating

Pass the token in the `Authorization` header as a `Bearer` token.

## Testing

```sh
vendor/bin/phpunit
vendor/bin/phpcs
```

## How To Contribute

Development happens at GitHub; any typical workflow using Pull Requests are welcome. In the same spirit, we use the GitHub issue tracker for all reports (regardless of the nature of the report, feature request, bugs, etc.).

All changes are supposed to be covered by unit tests, if testing is impossible or very unpractical that warrants a discussion in the comments section of the pull request.

### Code standard

As the library is intended for use in Laravel applications we encourage code standard to follow [upstream Laravel practices](https://laravel.com/docs/master/contributions#coding-style) - in short that would mean [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) and [PSR-4](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md).
