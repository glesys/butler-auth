<?php

namespace Butler\Auth;

use Butler\Auth\Contracts\HasAbilities;
use Butler\Auth\Facades\TokenCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model implements HasAbilities
{
    protected $casts = [
        'abilities' => 'json',
        'last_used_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'token',
        'abilities',
    ];

    protected $hidden = [
        'token',
    ];

    protected static function booted()
    {
        static::deleted(function ($accessToken) {
            TokenCache::forget($accessToken->token);
        });
    }

    public static function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public function tokenable()
    {
        return $this->morphTo('tokenable');
    }

    public function scopeByToken(Builder $query, string $hashedToken)
    {
        return $query->where('token', $hashedToken);
    }

    public function can(string $ability): bool
    {
        return in_array('*', $this->abilities)
            || in_array($ability, $this->abilities);
    }

    public function cant(string $ability): bool
    {
        return ! $this->can($ability);
    }
}
