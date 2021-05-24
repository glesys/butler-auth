<?php

namespace Butler\Auth;

use Butler\Auth\AccessToken;
use Butler\Auth\Contracts\HasAccessTokens;
use Butler\Auth\Facades\TokenCache;
use Butler\Auth\Jobs\UpdateAccessTokenLastUsed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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

    protected function findAccessToken(string $plainToken): ?AccessToken
    {
        $hashedToken = AccessToken::hash($plainToken);

        if ($cached = TokenCache::get($hashedToken)) {
            return $cached;
        }

        $accessToken = AccessToken::with('tokenable')->byToken($hashedToken)->first();

        if ($accessToken) {
            TokenCache::put($accessToken);
        }

        return $accessToken;
    }

    protected function supportsTokens(Model $tokenable = null): bool
    {
        return $tokenable && $tokenable instanceof HasAccessTokens;
    }
}
