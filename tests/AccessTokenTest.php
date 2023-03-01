<?php

namespace Butler\Auth\Tests;

use Butler\Auth\AccessToken;
use Butler\Auth\Contracts\HasAccessTokens;
use Butler\Auth\Facades\TokenCache;
use Butler\Auth\Tests\Models\ConsumerWithTokenSupport;

class AccessTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateDatabase();
    }

    public function test_deleted_event_forgets_cached_item()
    {
        $token = $this->createToken();

        TokenCache::expects('forget')->with($token->token);

        $token->delete();
    }

    public function test_hash()
    {
        $this->assertEquals(
            '2bb80d537b1da3e38bd30361aa855686bde0eacd7162fef6a25fe97bf527a25b',
            AccessToken::hash('secret'),
        );
    }

    public function test_tokenable()
    {
        $token = $this->createToken();

        $this->assertInstanceOf(HasAccessTokens::class, $token->tokenable);
    }

    public function test_scopeByToken()
    {
        $existingToken = $this->createToken();

        $foundToken = AccessToken::byToken($existingToken->token)->first();

        $this->assertTrue($foundToken->is($existingToken));
    }

    public function test_can_and_cannot()
    {
        $token = new AccessToken();

        $token->abilities = [];

        $this->assertFalse($token->can('foo'));

        $token->abilities = ['foo'];

        $this->assertTrue($token->can('foo'));
        $this->assertFalse($token->can('bar'));
        $this->assertTrue($token->cannot('bar'));
        $this->assertFalse($token->cannot('foo'));

        $token->abilities = ['foo', '*'];

        $this->assertTrue($token->can('foo'));
        $this->assertTrue($token->can('bar'));
    }

    private function createToken(string $token = 'secret'): AccessToken
    {
        return ConsumerWithTokenSupport::create()
            ->tokens()
            ->create(['token' => hash('sha256', $token)]);
    }
}
