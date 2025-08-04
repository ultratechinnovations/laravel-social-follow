<?php

namespace UltraTechInnovations\LaravelSocialFollow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \UltraTechInnovations\LaravelSocialFollow\LaravelSocialFollow
 */
class LaravelSocialFollow extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \UltraTechInnovations\LaravelSocialFollow\LaravelSocialFollow::class;
    }
}
