<?php

namespace Butler\Auth\Tests;

use Butler\Auth\JwtGuard;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class JwtGuardTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    protected function createRequest(string $token)
    {
        return Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => "Bearer {$token}"]);
    }

    public function test_user_returns_null_with_empty_token()
    {
        $request = Request::create('/', 'GET');
        $guard = new JwtGuard($request, 'key', []);

        $this->assertEquals(null, $guard->user());
    }

    public function test_user_returns_null_with_invalid_token()
    {
        $token = 'invalid-token';
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => "Bearer {$token}"]);
        $guard = new JwtGuard($request, 'key', []);

        $this->assertEquals(null, $guard->user());
    }

    public function test_user_returns_null_with_unsigned_token()
    {
        $token = (new Builder())
            ->setSubject('subject')
            ->getToken();

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => "Bearer {$token}"]);
        $guard = new JwtGuard($request, 'key', []);

        $this->assertEquals(null, $guard->user());
    }

    public function test_user_returns_null_with_incorrect_key()
    {
        $token = (new Builder())
            ->setSubject('subject')
            ->sign(new Sha256(), 'incorrect-key')
            ->getToken();

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => "Bearer {$token}"]);
        $guard = new JwtGuard($request, 'key', []);

        $this->assertEquals(null, $guard->user());
    }

    public function test_user_returns_user()
    {
        $token = (new Builder())
            ->setSubject('subject')
            ->sign(new Sha256(), 'key')
            ->getToken();

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => "Bearer {$token}"]);
        $guard = new JwtGuard($request, 'key', []);

        $this->assertEquals('subject', $guard->user()->sub);
    }

    public function test_user_returns_user_with_token_in_query()
    {
        $token = (new Builder())
            ->setSubject('subject')
            ->sign(new Sha256(), 'key')
            ->getToken();

        $request = Request::create('/', 'GET', ['token' => $token]);
        $guard = new JwtGuard($request, 'key', []);

        $this->assertEquals('subject', $guard->user()->sub);
    }

    public function test_user_returns_user_with_token_in_body()
    {
        $token = (new Builder())
            ->setSubject('subject')
            ->sign(new Sha256(), 'key')
            ->getToken();

        $request = Request::create('/', 'POST', ['token' => $token]);
        $guard = new JwtGuard($request, 'key', []);

        $this->assertEquals('subject', $guard->user()->sub);
    }

    public function test_user_returns_null_with_missing_subject()
    {
        $token = (new Builder())
            ->sign(new Sha256(), 'key')
            ->getToken();

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => "Bearer {$token}"]);
        $guard = new JwtGuard($request, 'key', []);

        $this->assertEquals(null, $guard->user());
    }

    public function test_user_returns_null_with_incorrect_required_aud_claim()
    {
        $token = (new Builder())
            ->setAudience('incorrect-audience')
            ->setSubject('subject')
            ->sign(new Sha256(), 'key')
            ->getToken();

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => "Bearer {$token}"]);
        $guard = new JwtGuard($request, 'key', [
            'aud' => 'correct-audience',
        ]);

        $this->assertEquals(null, $guard->user());
    }

    public function test_user_returns_null_with_incorrect_required_iss_claim()
    {
        $token = (new Builder())
            ->setIssuer('incorrect-issuer')
            ->setSubject('subject')
            ->sign(new Sha256(), 'key')
            ->getToken();

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => "Bearer {$token}"]);
        $guard = new JwtGuard($request, 'key', [
            'iss' => 'correct-issuer',
        ]);

        $this->assertEquals(null, $guard->user());
    }

    public function test_user_returns_user_with_correct_required_aud_claim()
    {
        $token = (new Builder())
            ->setAudience('correct-audience')
            ->setSubject('subject')
            ->sign(new Sha256(), 'key')
            ->getToken();

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => "Bearer {$token}"]);
        $guard = new JwtGuard($request, 'key', [
            'aud' => 'correct-audience',
        ]);

        $this->assertEquals('subject', $guard->user()->sub);
    }

    public function test_user_returns_user_with_correct_required_iss_claim()
    {
        $token = (new Builder())
            ->setIssuer('correct-issuer')
            ->setSubject('subject')
            ->sign(new Sha256(), 'key')
            ->getToken();

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => "Bearer {$token}"]);
        $guard = new JwtGuard($request, 'key', [
            'iss' => 'correct-issuer',
        ]);

        $this->assertEquals('subject', $guard->user()->sub);
    }

    public function test_user_returns_user_with_multiple_required_claims()
    {
        $token = (new Builder())
            ->setAudience('correct-audience')
            ->setIssuer('correct-issuer')
            ->setSubject('subject')
            ->sign(new Sha256(), 'key')
            ->getToken();

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => "Bearer {$token}"]);
        $guard = new JwtGuard($request, 'key', [
            'aud' => 'correct-audience',
            'iss' => 'correct-issuer',
        ]);

        $this->assertEquals('subject', $guard->user()->sub);
    }

    public function test_user_returns_user_from_provider_when_specified()
    {
        $provider = m::mock(UserProvider::class);
        $provider->shouldReceive('retrieveById')
            ->with('subject')
            ->andReturn(new GenericUser(['sub' => 'subject']));

        $token = (new Builder())
            ->setSubject('subject')
            ->sign(new Sha256(), 'key')
            ->getToken();

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => "Bearer {$token}"]);
        $guard = new JwtGuard($request, 'key', [], $provider);

        $this->assertEquals('subject', $guard->user()->sub);
    }

    public function test_validate_returns_false_with_incorrect_token()
    {
        $token = 'incorrect-token';

        $request = Request::create('/');
        $guard = new JwtGuard($request, 'key', []);

        $this->assertEquals(false, $guard->validate(['token' => $token]));
    }

    public function test_validate_returns_true_with_correct_token()
    {
        $token = (new Builder())
            ->setSubject('subject')
            ->sign(new Sha256(), 'key')
            ->getToken();

        $request = Request::create('/');
        $guard = new JwtGuard($request, 'key', []);

        $this->assertEquals(true, $guard->validate(['token' => $token]));
    }
}
