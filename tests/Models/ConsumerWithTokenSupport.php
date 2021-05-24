<?php

namespace Butler\Auth\Tests\Models;

use Butler\Auth\Concerns\HasAccessTokens;
use Butler\Auth\Contracts\HasAccessTokens as HasAccessTokensContract;
use Illuminate\Foundation\Auth\User;

class ConsumerWithTokenSupport extends User implements HasAccessTokensContract
{
    use HasAccessTokens;

    protected $table = 'consumers';
}
