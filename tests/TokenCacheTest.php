<?php

namespace Butler\Auth\Tests;

use Butler\Auth\AccessToken;
use Butler\Auth\TokenCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Mockery;

class TokenCacheTest extends TestCase
{
    public function test_key()
    {
        $this->assertEquals(
            'butler-auth-token-hashed-token',
            (new TokenCache())->key('hashed-token'),
        );
    }

    public function test_get()
    {
        $tokenCache = new TokenCache();
        $token = new AccessToken(['token' => 'foo']);

        Cache::shouldReceive('get')
            ->with($tokenCache->key('foo'))
            ->andReturn($token);

        Cache::shouldReceive('get')
            ->with($tokenCache->key('bar'))
            ->andReturnNull();

        $this->assertTrue($tokenCache->get('foo')->is($token));
        $this->assertNull($tokenCache->get('bar'));
    }

    public function test_put()
    {
        $this->travelTo(Date::parse('2021-05-29 12:00:00'));

        $tokenCache = new TokenCache();
        $token = new AccessToken(['token' => 'foo']);

        Cache::shouldReceive('put')
            ->with(
                $tokenCache->key('foo'),
                $token,
                Mockery::on(fn ($date) => $date->eq(now()->addDay()))
            )
            ->andReturnTrue();

        $this->assertTrue($tokenCache->put($token));
    }

    public function test_forget()
    {
        $tokenCache = new TokenCache();

        Cache::shouldReceive('forget')
            ->with($tokenCache->key('foo'))
            ->andReturnTrue();

        $this->assertTrue($tokenCache->forget('foo'));
    }
}
