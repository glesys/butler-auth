<?php

namespace Butler\Auth\Tests;

use Butler\Auth\AccessToken;
use Butler\Auth\TokenCache;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Mockery;

class TokenCacheTest extends TestCase
{
    private $repository;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->mock(Repository::class);

        Cache::shouldReceive('store')
            ->with(null)
            ->andReturn($this->repository)
            ->byDefault();
    }

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

        $this->repository->expects('get')
            ->with($tokenCache->key('foo'))
            ->andReturn($token);

        $this->repository->expects('get')
            ->with($tokenCache->key('bar'))
            ->andReturnNull();

        $this->assertTrue($tokenCache->get('foo')->is($token));
        $this->assertNull($tokenCache->get('bar'));
    }

    public function test_put()
    {
        $this->travelTo('2021-05-29 12:00:00');

        $tokenCache = new TokenCache();
        $token = new AccessToken(['token' => 'foo']);

        $this->repository->expects('put')
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

        $this->repository->expects('forget')
            ->with($tokenCache->key('foo'))
            ->andReturnTrue();

        $this->assertTrue($tokenCache->forget('foo'));
    }

    public function test_octane_is_used_if_running()
    {
        putenv('LARAVEL_OCTANE=1');

        $tokenCache = new TokenCache();

        Cache::expects('store')->with('octane')->andReturn($this->repository);

        $this->repository->expects('forget')
            ->with($tokenCache->key('foo'))
            ->andReturnTrue();

        $this->assertTrue($tokenCache->forget('foo'));

        putenv('LARAVEL_OCTANE=0');
    }
}
