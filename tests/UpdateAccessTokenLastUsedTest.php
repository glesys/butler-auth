<?php

namespace Butler\Auth\Tests;

use Butler\Auth\Facades\TokenCache;
use Butler\Auth\Jobs\UpdateAccessTokensLastUsed;
use Butler\Auth\Tests\Models\ConsumerWithTokenSupport;
use Illuminate\Support\Facades\Date;

class UpdateAccessTokenLastUsedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateDatabase();
    }

    public function test_happy_path()
    {
        $this->travelTo(Date::parse('2021-05-25 12:00:00'));

        $consumer = ConsumerWithTokenSupport::create();

        $token1 = $consumer->tokens()->create(['token' => 'abc123']);
        $token2 = $consumer->tokens()->create(['token' => 'bcd234']);
        $token3 = $consumer->tokens()->create(['token' => 'cde345']);

        $token1->forceFill(['last_used_at' => now()->subSecond()])->save();
        $token2->forceFill(['last_used_at' => now()->subSecond()])->save();

        $cachedToken1 = tap($token1->replicate())->setAttribute('last_used_at', now());
        $cachedToken2 = tap($token2->replicate())->setAttribute('last_used_at', now()->subMinute());

        TokenCache::shouldReceive('get')->with('abc123')->andReturn($cachedToken1);
        TokenCache::shouldReceive('get')->with('bcd234')->andReturn($cachedToken2);
        TokenCache::shouldReceive('get')->with('cde345')->andReturnNull();

        (new UpdateAccessTokensLastUsed())->handle();

        $this->assertEquals(now(), $token1->refresh()->last_used_at);
        $this->assertEquals(now()->subSecond(), $token2->refresh()->last_used_at);
        $this->assertNull($token3->refresh()->last_used_at);
    }
}
