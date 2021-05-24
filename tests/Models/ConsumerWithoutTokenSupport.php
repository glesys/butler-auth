<?php

namespace Butler\Auth\Tests\Models;

use Illuminate\Foundation\Auth\User;

class ConsumerWithoutTokenSupport extends User
{
    protected $table = 'consumers';
}
