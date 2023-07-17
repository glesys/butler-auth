<?php

namespace Butler\Auth;

use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class TokenCache
{
    public function key(string $hashedToken): string
    {
        return "butler-auth-token-{$hashedToken}";
    }

    public function get(string $hashedToken): ?AccessToken
    {
        return $this->cache()->get($this->key($hashedToken));
    }

    public function put(AccessToken $token): bool
    {
        return $this->cache()->put(
            $this->key($token->token),
            $token,
            now()->addDay(),
        );
    }

    public function forget($hashedToken): bool
    {
        return $this->cache()->forget($this->key($hashedToken));
    }

    private function cache(): Repository
    {
        return Cache::store(getenv('LARAVEL_OCTANE') ? 'octane' : null);
    }
}
