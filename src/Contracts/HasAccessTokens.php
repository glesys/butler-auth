<?php

namespace Butler\Auth\Contracts;

interface HasAccessTokens
{
    public function tokens();
    public function tokenCan(string $ability): bool;
    public function createToken(array $abilities = ['*'], string $name = null);
    public function currentAccessToken(): HasAbilities;
    public function withAccessToken(HasAbilities $accessToken): static;
}
