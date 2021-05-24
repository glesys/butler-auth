<?php

namespace Butler\Auth;

use Butler\Auth\AccessToken;
use Butler\Auth\Contracts\HasAccessTokens;
use Butler\Auth\Jobs\UpdateAccessTokenLastUsed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class Guard
{
    public function __invoke(Request $request)
    {
        if (! $token = $request->bearerToken()) {
            return;
        }

        if (! $accessToken = $this->findAccessToken($token)) {
            return;
        }

        dispatch(new UpdateAccessTokenLastUsed($accessToken));

        return $this->supportsTokens($accessToken->tokenable)
            ? $accessToken->tokenable->withAccessToken($accessToken)
            : null;
    }

    protected function findAccessToken(string $plainToken): ?Model
    {
        $hashedToken = AccessToken::hash($plainToken);
        $cacheKey = AccessToken::getCacheKey($hashedToken);

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $accessToken = AccessToken::with('tokenable')->byToken($hashedToken)->first();

        if ($accessToken) {
            Cache::put($cacheKey, $accessToken, now()->addDay());
        }

        return $accessToken;
    }

    protected function supportsTokens(Model $tokenable = null): bool
    {
        return $tokenable && $tokenable instanceof HasAccessTokens;
    }
}
