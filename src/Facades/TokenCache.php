<?php

namespace Butler\Auth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string key(string $hashedToken)
 * @method static \Butler\Auth\AccessToken|null get(string $hashedToken)
 * @method static bool put(\Butler\Auth\AccessToken $token)
 * @method static bool forget(string $hashedToken)
 *
 * @see \Butler\Auth\TokenCache
 */
class TokenCache extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Butler\Auth\TokenCache::class;
    }
}
