<?php

namespace Butler\Auth\Commands;

use Exception;
use Illuminate\Console\Command;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class GenerateToken extends Command
{
    protected $signature = 'butler-auth:generate-token';

    protected $description = 'Generate tokens';

    public function handle()
    {
        throw_unless(
            $key = config('butler.auth.secret_key'),
            new Exception('`butler.auth.secret_key` is required to generate tokens')
        );

        $subject = $this->ask('Please name the consumer. Example: `api.glesys.com`');

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($key)
        );

        $builder = $config->builder()
            ->identifiedBy(bin2hex(random_bytes(8)))
            ->issuedAt(now()->toDateTimeImmutable())
            ->relatedTo($subject);

        if ($audience = $this->ask(
            'Please name the audience.',
            collect(config('butler.auth.required_claims.aud'))->first()
        )) {
            $builder->permittedFor($audience);
        }

        if ($issuer = $this->ask(
            'Please name the issuer.',
            collect(config('butler.auth.required_claims.iss'))->first()
        )) {
            $builder->issuedBy($issuer);
        }

        $token = $builder->getToken($config->signer(), $config->signingKey());

        $this->line(json_encode($token->headers()->all(), JSON_PRETTY_PRINT));
        $this->line(json_encode($token->claims()->all(), JSON_PRETTY_PRINT));

        $this->info($token->toString());
    }
}
