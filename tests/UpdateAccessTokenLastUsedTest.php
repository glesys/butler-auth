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

        $cachedToken1 = tap($token1->replicate())->setAttribute('last_used_at', '2021-05-25 13:00:00');
        $cachedToken2 = tap($token2->replicate())->setAttribute('last_used_at', '2021-05-25 14:00:00');

        TokenCache::shouldReceive('get')->with('abc123')->andReturn($cachedToken1);
        TokenCache::shouldReceive('get')->with('bcd234')->andReturn($cachedToken2);
        TokenCache::shouldReceive('get')->with('cde345')->andReturnNull();

        (new UpdateAccessTokensLastUsed())->handle();

        $this->assertEquals('2021-05-25 13:00:00', $token1->refresh()->last_used_at->toDateTimeString());
        $this->assertEquals('2021-05-25 14:00:00', $token2->refresh()->last_used_at->toDateTimeString());
        $this->assertNull($token3->refresh()->last_used_at);
    }
}
