<?php

namespace Butler\Auth\Jobs;

use Butler\Auth\AccessToken;
use Butler\Auth\Facades\TokenCache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateAccessTokensLastUsed implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue;
    use Dispatchable;
    use Queueable;
    use SerializesModels;

    public function handle()
    {
        foreach (AccessToken::all() as $token) {
            $cachedToken = TokenCache::get($token->token);

            if ($cachedToken?->last_used_at > $token->last_used_at) {
                $token->last_used_at = $cachedToken->last_used_at;
                $token->save();
            }
        }
    }
}
