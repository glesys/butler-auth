<?php

namespace Butler\Auth\Concerns;

use Butler\Auth\AccessToken;
use Butler\Auth\Contracts\HasAbilities;
use Butler\Auth\NewAccessToken;

trait HasAccessTokens
{
    protected $accessToken;

    public function tokens()
    {
        return $this->morphMany(AccessToken::class, 'tokenable');
    }

    public function tokenCan(string $ability): bool
    {
        return $this->accessToken ? $this->accessToken->can($ability) : false;
    }

    public function createToken(array $abilities = ['*'], string $name = null)
    {
        $token = $this->tokens()->create([
            'token' => hash('sha256', $plainToken = str()->random(40)),
            'abilities' => $abilities,
            'name' => $name,
        ]);

        return new NewAccessToken($token, $plainToken);
    }

    public function currentAccessToken(): HasAbilities
    {
        return $this->accessToken;
    }

    public function withAccessToken(HasAbilities $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}
