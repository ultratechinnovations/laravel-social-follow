<?php

namespace UltraTechInnovations\SocialFollow\Commands;

use Illuminate\Console\Command;

class LaravelSocialFollowCommand extends Command
{
    public $signature = 'laravel-social-follow';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
