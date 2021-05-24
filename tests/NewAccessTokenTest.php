<?php

namespace Butler\Auth\Tests;

use Butler\Auth\AccessToken;
use Butler\Auth\NewAccessToken;
use PHPUnit\Framework\TestCase;

class NewAccessTokenTest extends TestCase
{
    public function test_toArray()
    {
        $newToken = new NewAccessToken($accessToken = new AccessToken(), 'plain');

        $this->assertEquals([
            'accessToken' => $accessToken,
            'plainTextToken' => 'plain',
        ], $newToken->toArray());
    }

    public function test_toJson()
    {
        $newToken = new NewAccessToken($accessToken = new AccessToken(), 'plain');

        $this->assertEquals(
            '{"accessToken":[],"plainTextToken":"plain"}',
            $newToken->toJson()
        );
    }
}
