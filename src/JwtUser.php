<?php

namespace Butler\Auth;

use Illuminate\Auth\GenericUser;

class JwtUser extends GenericUser
{
    public function getAuthIdentifierName()
    {
        return 'sub';
    }
}
