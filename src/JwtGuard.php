<?php

namespace Butler\Auth;

use Exception;
use Illuminate\Auth\GenericUser;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token;

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
     * The secret key used for token verification.
     *
     * @var string
     */
    protected $secretKey;

    /**
     * The required claims used for token validation.
     *
     * @var array
     */
    protected $requiredClaims;

    /**
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $secretKey
     * @param  array  $requiredClaims
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @return void
     */
    public function __construct(Request $request, string $secretKey, array $requiredClaims, UserProvider $provider = null)
    {
        $this->request = $request;
        $this->secretKey = $secretKey;
        $this->requiredClaims = $requiredClaims;
        $this->provider = $provider;
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
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function authenticateToken(string $accessToken)
    {
        try {
            $token = $this->parseToken($accessToken);

            if (! $token) {
                return null;
            }

            if ($this->validateToken($token) && $this->verifyToken($token)) {
                return $this->createUser($token);
            }
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * Parse the token.
     *
     * @param  string  $token
     * @return \Lcobucci\JWT\Token|null
     */
    protected function parseToken(string $accessToken)
    {
        return (new Parser())->parse($accessToken);
    }

    /**
     * Validate a token and ensure trusted audience and issuer.
     *
     * @param  \Lcobucci\JWT\Token  $token
     * @return bool
     */
    protected function validateToken(Token $token)
    {
        if (empty($token->getClaim('sub', ''))) {
            return false;
        }

        foreach ($this->requiredClaims as $claim => $value) {
            if ($claim == 'aud' && collect($value)->intersect($token->getClaim('aud', ''))->isEmpty()) {
                return false;
            }
            if ($claim == 'iss' && collect($value)->contains($token->getClaim('iss', '')) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verify a token as signed with a trusted key.
     *
     * @param  \Lcobucci\JWT\Token  $token
     * @return bool
     */
    protected function verifyToken(Token $token)
    {
        return $token->verify(new Sha256(), $this->secretKey);
    }

    /**
     * Create a user.
     *
     * @param  \Lcobucci\JWT\Token  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function createUser(Token $token)
    {
        if ($this->provider) {
            return $this->provider->retrieveById($token->getClaim('sub'));
        }

        return new GenericUser($token->getClaims());
    }
}
