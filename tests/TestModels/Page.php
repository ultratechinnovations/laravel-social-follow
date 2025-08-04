<?php

namespace Tests\TestModels;

use Illuminate\Database\Eloquent\Model;
use UltraTechInnovations\SocialFollow\Traits\CanBeFollowed;

class Page extends Model
{
    use CanBeFollowed;

    protected $table = 'test_pages';

    protected $fillable = ['name'];
}
