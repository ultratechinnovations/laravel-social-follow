<?php

namespace UltraTechInnovations\SocialFollow;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use UltraTechInnovations\SocialFollow\Commands\LaravelSocialFollowCommand;

class LaravelSocialFollowServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-social-follow')
            ->hasConfigFile()
            // ->hasViews()
            ->hasMigrations(['create_follows_table', 'create_blocks_table'])
            ->hasCommand(LaravelSocialFollowCommand::class);
    }
}
