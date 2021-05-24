<?php

namespace Butler\Auth\Tests;

use Butler\Auth\AccessToken;
use Butler\Auth\NewAccessToken;
use Butler\Auth\Tests\Models\ConsumerWithTokenSupport;

class HasAccessTokensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateDatabase();
    }

    public function test_tokens_relation()
    {
        $token = (ConsumerWithTokenSupport::create())
            ->tokens()
            ->create(['token' => hash('sha256', 'secret')]);

        $this->assertInstanceOf(AccessToken::class, $token);
    }

    public function test_tokenCan()
    {
        $consumer = ConsumerWithTokenSupport::create();

        $token = $consumer->tokens()->create([
            'token' => hash('sha256', 'secret'),
            'abilities' => ['foo'],
        ]);

        $consumer->withAccessToken($token);

        $this->assertTrue($consumer->tokenCan('foo'));
    }

    public function test_createToken()
    {
        $newToken = (ConsumerWithTokenSupport::create())->createToken(['foo', 'bar'], 'baz');

        $accessToken = AccessToken::sole();

        $this->assertInstanceOf(NewAccessToken::class, $newToken);
        $this->assertEquals(40, strlen($newToken->plainTextToken));
        $this->assertTrue($newToken->accessToken->is($accessToken));
        $this->assertEquals($accessToken->token, hash('sha256', $newToken->plainTextToken));
    }

    public function test_currentAccessToken()
    {
        $consumer = ConsumerWithTokenSupport::create();

        $token = $consumer->tokens()->create(['token' => hash('sha256', 'secret')]);

        $consumer->withAccessToken($token);

        $this->assertTrue($consumer->currentAccessToken()->is($token));
    }
}
