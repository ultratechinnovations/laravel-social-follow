<?php

namespace UltraTechInnovations\SocialFollow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Block extends Model
{
    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $prefix = config('social-follow.table_prefix');
        $this->table = $prefix.'blocks';
    }

    protected $fillable = [
        'blocker_id',
        'blocker_type',
        'blockable_id',
        'blockable_type',
    ];

    public function blocker(): MorphTo
    {
        return $this->morphTo();
    }

    public function blockable(): MorphTo
    {
        return $this->morphTo();
    }
}
