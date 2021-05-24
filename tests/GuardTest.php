<?php

namespace Butler\Auth\Tests;

use Butler\Auth\AccessToken;
use Butler\Auth\Facades\TokenCache;
use Butler\Auth\Guard;
use Butler\Auth\Jobs\UpdateAccessTokenLastUsed;
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

        $token = AccessToken::forceCreate([
            'tokenable_id' => $consumer->id,
            'tokenable_type' => $consumer::class,
            'token' => hash('sha256', 'secret'),
        ]);

        $this->assertNull((new Guard())->__invoke($this->makeRequest()));

        Queue::assertPushed(fn (UpdateAccessTokenLastUsed $job) => $job->accessToken->is($token));
    }

    public function test_authentication_is_successful_with_valid_token()
    {
        $consumer = ConsumerWithTokenSupport::create();
        $token = $consumer->tokens()->create(['token' => hash('sha256', 'secret')]);

        TokenCache::shouldReceive('get');
        TokenCache::shouldReceive('put');

        $returnedConsumer = (new Guard())->__invoke($this->makeRequest());

        Queue::assertPushed(fn (UpdateAccessTokenLastUsed $job) => $job->accessToken->is($token));

        $this->assertEquals($consumer->id, $returnedConsumer->id);
        $this->assertEquals($token->id, $returnedConsumer->currentAccessToken()->id);
    }

    public function test_access_token_is_cached_when_found()
    {
        $this->travelTo(Date::parse('2021-05-25 12:00:00'));

        $consumer = ConsumerWithTokenSupport::create();
        $token = $consumer->tokens()->create(['token' => hash('sha256', 'secret')]);

        TokenCache::shouldReceive('get')
            ->with($token->token)
            ->andReturnNull();

        TokenCache::shouldReceive('put')->with(
            Mockery::on(fn ($receivedToken) => $receivedToken->is($token)),
        );

        (new Guard())->__invoke($this->makeRequest());
    }

    public function test_access_token_is_retrieved_from_cache_when_found()
    {
        $consumer = ConsumerWithTokenSupport::create();
        $token = $consumer->tokens()->create(['token' => hash('sha256', 'secret')]);

        TokenCache::shouldReceive('get')
            ->with($token->token)
            ->andReturn($token);

        $returnedConsumer = (new Guard())->__invoke($this->makeRequest());

        $this->assertEquals($consumer->id, $returnedConsumer->id);
    }

    private function makeRequest(string $token = 'secret'): Request
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('Authorization', "Bearer {$token}");

        return $request;
    }
}
