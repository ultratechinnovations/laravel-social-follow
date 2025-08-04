<?php

namespace Tests\TestModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use UltraTechInnovations\SocialFollow\Traits\CanFollow;
use UltraTechInnovations\SocialFollow\Traits\CanBeFollowed;

class User extends Model
{
    use Notifiable;
    use CanFollow, CanBeFollowed;

    protected $table = 'test_users';

    protected $fillable = ['name'];
}
