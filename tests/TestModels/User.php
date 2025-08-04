<?php

namespace Tests\TestModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use UltraTechInnovations\SocialFollow\Traits\CanBeFollowed;
use UltraTechInnovations\SocialFollow\Traits\CanFollow;

class User extends Model
{
    use CanBeFollowed, CanFollow;
    use Notifiable;

    protected $table = 'test_users';

    protected $fillable = ['name'];
}
