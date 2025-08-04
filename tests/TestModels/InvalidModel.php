<?php

namespace Tests\TestModels;

use Illuminate\Database\Eloquent\Model;

class InvalidModel extends Model
{
    protected $table = 'test_invalid_models';

    protected $fillable = ['name'];
}
