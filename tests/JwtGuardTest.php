<?php

namespace Butler\Auth\Tests;

use Butler\Auth\JwtGuard;
use Butler\Auth\JwtUser;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class JwtGuardTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function test_user_returns_null_with_empty_token()
    {
        $request = Request::create('/');
        $guard = new JwtGuard($request, 'key', []);

        $this->assertEquals(null, $guard->user());
    }

    public function test_user_returns_null_with_invalid_token()
    {
        $request = Request::create("/?token=invalid-token");
        $guard = new JwtGuard($request, 'key', []);

        $this->assertEquals(null, $guard->user());
    }

    public function test_user_returns_null_with_unsigned_token()
    {
        $token = 'eyJhbGciOiJub25lIn0.eyJpc3MiOiJqb2UiLA0KICJleHAiOjEzMDA4MTkzODAsDQogImh0dHA6Ly9leGFtcGxlLmNvbS9pc19yb290Ijp0cnVlfQ.';
        $request = Request::create("/?token={$token}");
        $guard = new JwtGuard($request, 'correct-key', []);

        $this->assertEquals(null, $guard->user());
    }

    public function test_user_returns_null_with_incorrect_key()
    {
        $config = $this->configForKey('incorrect-key');

        $token = $config->builder()
            ->relatedTo('subject')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $request = Request::create("/?token={$token}");
        $guard = new JwtGuard($request, 'correct-key', []);

        $this->assertEquals(null, $guard->user());
    }

    public function test_user_returns_user_with_correct_key()
    {
        $config = $this->configForKey('correct-key');

        $token = $config->builder()
            ->relatedTo('subject')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $request = Request::create("/?token={$token}");
        $guard = new JwtGuard($request, 'correct-key', []);

        $this->assertInstanceOf(JwtUser::class, $guard->user());
    }

    public function test_user_returns_user_returning_sub_for_getAuthIdentifier()
    {
        $config = $this->configForKey('correct-key');

        $token = $config->builder()
            ->relatedTo('subject')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $request = Request::create("/?token={$token}");
        $guard = new JwtGuard($request, 'correct-key', []);

        $this->assertEquals('subject', $guard->user()->getAuthIdentifier());
    }

    public function test_user_returns_user_with_token_in_query()
    {
        $config = $this->configForKey('correct-key');

        $token = $config->builder()
            ->relatedTo('subject')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $request = Request::create("/?token={$token}");
        $guard = new JwtGuard($request, 'correct-key', []);

        $this->assertInstanceOf(JwtUser::class, $guard->user());
    }

    public function test_user_returns_user_with_token_in_header()
    {
        $config = $this->configForKey('correct-key');

        $token = $config->builder()
            ->relatedTo('subject')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => "Bearer {$token}"]);
        $guard = new JwtGuard($request, 'correct-key', []);

        $this->assertInstanceOf(JwtUser::class, $guard->user());
    }

    public function test_user_returns_user_with_token_in_body()
    {
        $config = $this->configForKey('correct-key');

        $token = $config->builder()
            ->relatedTo('subject')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $request = Request::create('/', 'POST', ['token' => $token]);
        $guard = new JwtGuard($request, 'correct-key', []);

        $this->assertInstanceOf(JwtUser::class, $guard->user());
    }

    public function test_user_returns_null_with_missing_subject()
    {
        $config = $this->configForKey('correct-key');

        $token = $config->builder()
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $request = Request::create("/?token={$token}");
        $guard = new JwtGuard($request, 'correct-key', []);

        $this->assertNull($guard->user());
    }

    public function test_user_returns_null_with_incorrect_required_aud_claim()
    {
        $config = $this->configForKey('correct-key');

        $token = $config->builder()
            ->permittedFor('incorrect-audience')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $request = Request::create("/?token={$token}");
        $guard = new JwtGuard($request, 'correct-key', [
            'aud' => 'correct-audience',
        ]);

        $this->assertNull($guard->user());
    }

    public function test_user_returns_null_with_incorrect_required_iss_claim()
    {
        $config = $this->configForKey('correct-key');

        $token = $config->builder()
            ->issuedBy('incorrect-issuer')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $request = Request::create("/?token={$token}");
        $guard = new JwtGuard($request, 'correct-key', [
            'iss' => 'correct-issuer',
        ]);

        $this->assertNull($guard->user());
    }

    public function test_user_returns_user_with_correct_required_aud_claim()
    {
        $config = $this->configForKey('correct-key');

        $token = $config->builder()
            ->relatedTo('subject')
            ->permittedFor('correct-audience')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $request = Request::create("/?token={$token}");
        $guard = new JwtGuard($request, 'correct-key', [
            'aud' => 'correct-audience',
        ]);

        $this->assertInstanceOf(JwtUser::class, $guard->user());
    }

    public function test_user_returns_user_with_correct_required_iss_claim()
    {
        $config = $this->configForKey('correct-key');

        $token = $config->builder()
            ->relatedTo('subject')
            ->issuedBy('correct-issuer')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $request = Request::create("/?token={$token}");
        $guard = new JwtGuard($request, 'correct-key', [
            'iss' => 'correct-issuer',
        ]);

        $this->assertInstanceOf(JwtUser::class, $guard->user());
    }

    public function test_user_returns_user_with_multiple_required_claims()
    {
        $config = $this->configForKey('correct-key');

        $token = $config->builder()
            ->relatedTo('subject')
            ->permittedFor('correct-audience')
            ->issuedBy('correct-issuer')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $request = Request::create("/?token={$token}");
        $guard = new JwtGuard($request, 'correct-key', [
            'aud' => 'correct-audience',
            'iss' => 'correct-issuer',
        ]);

        $this->assertInstanceOf(JwtUser::class, $guard->user());
    }

    public function test_user_returns_user_with_multiple_required_claims_with_arrays()
    {
        $config = $this->configForKey('correct-key');

        $token = $config->builder()
            ->relatedTo('subject')
            ->permittedFor('correct-audience')
            ->issuedBy('correct-issuer')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $request = Request::create("/?token={$token}");
        $guard = new JwtGuard($request, 'correct-key', [
            'aud' => ['another-audience', 'correct-audience'],
            'iss' => ['another-issuer', 'correct-issuer'],
        ]);

        $this->assertInstanceOf(JwtUser::class, $guard->user());
    }

    public function test_user_returns_user_from_provider_when_specified()
    {
        $provider = m::mock(UserProvider::class);
        $provider->shouldReceive('retrieveById')
            ->with('subject')
            ->andReturn(new GenericUser(['sub' => 'subject']));

        $config = $this->configForKey('correct-key');

        $token = $config->builder()
            ->relatedTo('subject')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $request = Request::create("/?token={$token}");
        $guard = new JwtGuard($request, 'correct-key', [], $provider);

        $this->assertInstanceOf(GenericUser::class, $guard->user());
    }

    public function test_validate_returns_false_with_incorrect_token()
    {
        $request = Request::create('/');
        $guard = new JwtGuard($request, 'correct-key', []);

        $this->assertFalse($guard->validate(['token' => 'incorrect-token']));
    }

    public function test_validate_returns_true_with_correct_token()
    {
        $config = $this->configForKey('correct-key');

        $token = $config->builder()
            ->relatedTo('subject')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $request = Request::create('/');
        $guard = new JwtGuard($request, 'correct-key', []);

        $this->assertTrue($guard->validate(['token' => $token]));
    }

    private function configForKey(string $key)
    {
        return Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($key)
        );
    }
}
