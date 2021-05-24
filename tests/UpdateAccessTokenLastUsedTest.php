<?php

namespace Butler\Auth\Tests;

use Butler\Auth\Jobs\UpdateAccessTokenLastUsed;
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

        $token = ConsumerWithTokenSupport::create()->tokens()->create(['token' => 'abc123']);

        $this->assertNull($token->last_used_at);

        $job = new UpdateAccessTokenLastUsed($token);

        $this->travel(1)->minutes();

        $job->handle();

        $this->assertEquals('2021-05-25 12:00:00', $token->last_used_at->toDateTimeString());
    }
}
