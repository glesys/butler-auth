<?php

namespace Butler\Auth\Jobs;

use Butler\Auth\AccessToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateAccessTokenLastUsed implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $date;

    public function __construct(public AccessToken $accessToken)
    {
        $this->date = now();
    }

    public function handle()
    {
        $this->accessToken
            ->forceFill(['last_used_at' => $this->date])
            ->save();
    }
}
