<?php

namespace Butler\Auth\Commands;

use Exception;
use Illuminate\Console\Command;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class GenerateToken extends Command
{
    protected $signature = 'token:generate';

    protected $description = 'Generate tokens';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        throw_unless(
            $key = config('butler.auth.secret_key'),
            new Exception('`butler.auth.secret_key` is required to generate tokens')
        );

        $subject = $this->ask('Please name the consumer. Example: `api.glesys.com`');

        $builder = new Builder();
        $builder->setId(bin2hex(random_bytes(8)))
            ->setIssuedAt(time())
            ->setSubject($subject);

        if ($audience = $this->ask(
            'Please name the audience.',
            collect(config('butler.auth.required_claims.aud'))->first()
        )) {
            $builder->setAudience($audience);
        }

        if ($issuer = $this->ask(
            'Please name the issuer.',
            collect(config('butler.auth.required_claims.iss'))->first()
        )) {
            $builder->setIssuer($issuer);
        }

        $token = $builder->sign(new Sha256(), $key)->getToken();

        $this->line(json_encode($token->getHeaders(), JSON_PRETTY_PRINT));
        $this->line(json_encode($token->getClaims(), JSON_PRETTY_PRINT));

        $this->info($token);
    }
}
