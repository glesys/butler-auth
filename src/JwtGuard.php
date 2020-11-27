<?php

namespace Butler\Auth;

use Exception;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

class JwtGuard implements Guard
{
    use GuardHelpers;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The required claims used for token validation.
     *
     * @var array
     */
    protected $requiredClaims = [];

    /**
     * JWT configuration object.
     *
     * @var \Lcobucci\JWT\Configuration
     */
    protected $config;

    /**
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $secretKey  The secret key used for token verification.
     * @param  array  $requiredClaims  The required claims used for token validation.
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @return void
     */
    public function __construct(
        Request $request,
        string $secretKey,
        array $requiredClaims,
        UserProvider $provider = null
    ) {
        $this->request = $request;
        $this->requiredClaims = $requiredClaims;
        $this->provider = $provider;

        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secretKey)
        );
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        $token = $this->getTokenForRequest();

        $user = null;

        if (! empty($token)) {
            $user = $this->authenticateToken($token);
        }

        return $this->user = $user;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        if (empty($credentials['token'])) {
            return false;
        }

        if ($this->authenticateToken($credentials['token'])) {
            return true;
        }

        return false;
    }

    /**
     * Get the token for the current request.
     *
     * @return string
     */
    protected function getTokenForRequest()
    {
        return $this->request->query('token')
            ?? $this->request->input('token')
            ?? $this->request->bearerToken();
    }

    /**
     * Authenticate the access token.
     *
     * @param  string  $accessToken
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function authenticateToken(string $accessToken)
    {
        try {
            $token = $this->parseToken($accessToken);

            if (! $token) {
                return null;
            }

            if ($this->validateToken($token)) {
                return $this->createUser($token);
            }
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * Parse the token.
     *
     * @param  string  $accessToken
     * @return \Lcobucci\JWT\Token\Plain|null
     */
    protected function parseToken(string $accessToken)
    {
        try {
            return $this->config->parser()->parse($accessToken);
        } catch (Exception $_) {
            return null;
        }
    }

    /**
     * Validate a token and ensure trusted audience and issuer.
     *
     * @param  \Lcobucci\JWT\Token\Plain  $token
     * @return bool
     */
    protected function validateToken(Plain $token)
    {
        if (empty($token->claims()->get('sub'))) {
            return false;
        }

        if ($audiences = Arr::wrap(data_get($this->requiredClaims, 'aud'))) {
            if (collect($audiences)->intersect($token->claims()->get('aud'))->isEmpty()) {
                return false;
            }
        }

        $constraints = [
            new SignedWith($this->config->signer(), $this->config->signingKey())
        ];

        if ($issuers = Arr::wrap(data_get($this->requiredClaims, 'iss'))) {
            $constraints[] = new IssuedBy(...$issuers);
        }

        return $this->config->validator()->validate($token, ...$constraints);
    }

    /**
     * Create a user.
     *
     * @param  \Lcobucci\JWT\Token\Plain  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function createUser(Plain $token)
    {
        if ($this->provider) {
            return $this->provider->retrieveById($token->claims()->get('sub'));
        }

        return new JwtUser($token->claims()->all());
    }
}
