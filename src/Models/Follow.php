<?php

namespace UltraTechInnovations\SocialFollow\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Follow extends Model
{
    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $prefix = config('social-follow.table_prefix');
        $this->table = $prefix.'follows';
    }

    protected $fillable = [
        'follower_id',
        'follower_type',
        'followable_id',
        'followable_type',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function follower(): MorphTo
    {
        return $this->morphTo();
    }

    public function followable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->whereNotNull('accepted_at');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('accepted_at');
    }

    public function scopeFollowerType(Builder $query, string $type): Builder
    {
        return $query->where('follower_type', $type);
    }

    public function scopeFollowableType(Builder $query, string $type): Builder
    {
        return $query->where('followable_type', $type);
    }
}
