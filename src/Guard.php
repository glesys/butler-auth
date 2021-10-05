<?php

namespace Butler\Auth;

use Butler\Auth\AccessToken;
use Butler\Auth\Contracts\HasAccessTokens;
use Butler\Auth\Facades\TokenCache;
use Butler\Auth\Jobs\UpdateAccessTokensLastUsed;
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

        if ($this->supportsTokens($accessToken->tokenable)) {
            UpdateAccessTokensLastUsed::dispatch()->delay(now()->addMinute());

            return $accessToken->tokenable->withAccessToken($accessToken);
        }
    }

    protected function findAccessToken(string $plainToken): ?AccessToken
    {
        $hashedToken = AccessToken::hash($plainToken);

        if (! $accessToken = rescue(fn () => TokenCache::get($hashedToken))) {
            $accessToken = AccessToken::with('tokenable')->byToken($hashedToken)->first();
        }

        if ($accessToken) {
            $accessToken->last_used_at = now();
            rescue(fn () => TokenCache::put($accessToken));
        }

        return $accessToken;
    }

    protected function supportsTokens(Model $tokenable = null): bool
    {
        return $tokenable && $tokenable instanceof HasAccessTokens;
    }
}
