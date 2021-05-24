<?php

namespace Butler\Auth;

use Butler\Auth\AccessToken;
use Butler\Auth\Contracts\HasAccessTokens;
use Mockery;

class ButlerAuth
{
    public static function actingAs(
        HasAccessTokens $consumer,
        array $abilities = [],
        string $guard = 'butler',
    ): HasAccessTokens {
        $token = Mockery::mock(AccessToken::class)->shouldIgnoreMissing(false);

        if (in_array('*', $abilities)) {
            $token->allows()->can(Mockery::any())->andReturnTrue();
        } else {
            foreach ($abilities as $ability) {
                $token->allows()->can($ability)->andReturnTrue();
            }
        }

        $consumer->withAccessToken($token);

        if (isset($consumer->wasRecentlyCreated) && $consumer->wasRecentlyCreated) {
            $consumer->wasRecentlyCreated = false;
        }

        app('auth')->guard($guard)->setUser($consumer);

        app('auth')->shouldUse($guard);

        return $consumer;
    }
}
