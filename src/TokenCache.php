<?php

namespace Butler\Auth;

use Butler\Auth\AccessToken;
use Illuminate\Support\Facades\Cache;

class TokenCache
{
    public function key(string $hashedToken): string
    {
        return "butler-auth-token-{$hashedToken}";
    }

    public function get(string $hashedToken): ?AccessToken
    {
        return Cache::get($this->key($hashedToken));
    }

    public function put(AccessToken $token): bool
    {
        return Cache::put($this->key($token->token), $token, now()->addDay());
    }

    public function forget($hashedToken): bool
    {
        return Cache::forget($this->key($hashedToken));
    }
}
