<?php

namespace Butler\Auth\Tests;

use Butler\Auth\AccessToken;
use Butler\Auth\Facades\TokenCache;
use Butler\Auth\Guard;
use Butler\Auth\Jobs\UpdateAccessTokensLastUsed;
use Butler\Auth\Tests\Models\ConsumerWithoutTokenSupport;
use Butler\Auth\Tests\Models\ConsumerWithTokenSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Mockery;

class GuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->migrateDatabase();
    }

    public function test_authentication_fails_without_token_in_request()
    {
        $this->assertNull((new Guard())->__invoke(Request::create('/', 'GET')));
    }

    public function test_authentication_fails_if_token_is_not_found()
    {
        $this->assertNull((new Guard())->__invoke($this->makeRequest()));
    }

    public function test_authentication_fails_if_consumer_does_not_support_tokens()
    {
        $consumer = ConsumerWithoutTokenSupport::create();

        AccessToken::forceCreate([
            'tokenable_id' => $consumer->id,
            'tokenable_type' => $consumer::class,
            'token' => hash('sha256', 'secret'),
        ]);

        $this->assertNull((new Guard())->__invoke($this->makeRequest()));

        Queue::assertNotPushed(UpdateAccessTokensLastUsed::class);
    }

    public function test_authentication_is_successful_with_valid_token()
    {
        $consumer = ConsumerWithTokenSupport::create();
        $token = $consumer->tokens()->create(['token' => hash('sha256', 'secret')]);

        $returnedConsumer = (new Guard())->__invoke($this->makeRequest());

        Queue::assertPushed(UpdateAccessTokensLastUsed::class);

        $this->assertEquals($consumer->id, $returnedConsumer->id);
        $this->assertEquals($token->id, $returnedConsumer->currentAccessToken()->id);
    }

    public function test_UpdateAccessTokensLastUsed_job_is_dispatched_with_delay()
    {
        $this->travelTo(Date::parse('2021-05-25 12:00:00'));

        ConsumerWithTokenSupport::create()
            ->tokens()
            ->create(['token' => hash('sha256', 'secret')]);

        (new Guard())->__invoke($this->makeRequest());

        Queue::assertPushed(function (UpdateAccessTokensLastUsed $job) {
            return $job->delay->toDateTimeString() === '2021-05-25 12:01:00';
        });
    }

    public function test_access_token_is_cached_correctly_when_found_in_database()
    {
        $this->travelTo(Date::parse('2021-05-25 12:00:00'));

        $consumer = ConsumerWithTokenSupport::create();
        $token = $consumer->tokens()->create([
            'token' => hash('sha256', 'secret'),
            'last_used_at' => null,
        ]);

        TokenCache::shouldReceive('get')->with($token->token)->andReturnNull();
        TokenCache::shouldReceive('put')->with(Mockery::on(fn ($receivedToken)
            => $receivedToken->is($token)
            && $receivedToken->last_used_at->toDateTimeString() === '2021-05-25 12:00:00'));

        (new Guard())->__invoke($this->makeRequest());
    }

    public function test_access_token_is_retrieved_from_cache_when_found_in_cache()
    {
        $consumer = ConsumerWithTokenSupport::create();
        $token = $consumer->tokens()->create(['token' => hash('sha256', 'secret')]);

        TokenCache::shouldReceive('get')->with($token->token)->andReturn($token);
        TokenCache::shouldReceive('put')->with(
            Mockery::on(fn ($receivedToken) => $receivedToken->is($token)),
        );

        $returnedConsumer = (new Guard())->__invoke($this->makeRequest());

        $this->assertTrue($returnedConsumer->is($consumer));
    }

    public function test_authentication_is_successful_even_if_cache_throws_exception()
    {
        $consumer = ConsumerWithTokenSupport::create();
        $token = $consumer->tokens()->create(['token' => hash('sha256', 'secret')]);

        $exception = new \Exception('Could not connect to redis');

        TokenCache::shouldReceive('get')->once()->andThrow($exception);
        TokenCache::shouldReceive('put')->once()->andThrow($exception);

        $returnedConsumer = (new Guard())->__invoke($this->makeRequest());

        Queue::assertPushed(UpdateAccessTokensLastUsed::class);

        $this->assertTrue($returnedConsumer->is($consumer));
    }

    private function makeRequest(string $token = 'secret'): Request
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', "Bearer {$token}");

        return $request;
    }
}
