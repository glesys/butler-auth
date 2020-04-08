<?php

namespace Butler\Auth\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Str;

class GenerateSecretKey extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'butler-auth:generate-secret-key
                    {--show : Display the key instead of modifying files}
                    {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the butler-auth secret key';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $key = $this->generateRandomKey();

        if ($this->option('show')) {
            return $this->line('<comment>' . $key . '</comment>');
        }

        if (! $this->setKeyInEnvironmentFile($key)) {
            return;
        }

        $this->laravel['config']['butler.auth.secret_key'] = $key;

        $this->info('Secret key set successfully.');
    }

    /**
     * Generate a random key.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return (string) Str::uuid()->getHex();
    }

    /**
     * Set the application key in the environment file.
     *
     * @param  string  $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile($key)
    {
        $currentKey = $this->laravel['config']['butler.auth.secret_key'];

        if (strlen($currentKey) !== 0 && (! $this->confirmToProceed())) {
            return false;
        }

        $this->writeNewEnvironmentFileWith($key);

        return true;
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param  string  $key
     * @return void
     */
    protected function writeNewEnvironmentFileWith($key)
    {
        $contents = file_get_contents($this->laravel->environmentFilePath());

        if (! preg_match('/^BUTLER_AUTH_SECRET_KEY=/m', $contents)) {
            $contents .= "\nBUTLER_AUTH_SECRET_KEY=";
            file_put_contents($this->laravel->environmentFilePath(), $contents);
        }

        file_put_contents(
            $this->laravel->environmentFilePath(),
            preg_replace(
                $this->keyReplacementPattern(),
                'BUTLER_AUTH_SECRET_KEY=' . $key,
                $contents
            )
        );
    }

    /**
     * Get a regex pattern that will match env
     * BUTLER_AUTH_SECRET_KEY with any random key.
     *
     * @return string
     */
    protected function keyReplacementPattern()
    {
        $escaped = preg_quote('=' . $this->laravel['config']['butler.auth.secret_key'], '/');

        return "/^BUTLER_AUTH_SECRET_KEY{$escaped}/m";
    }
}
