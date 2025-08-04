<?php

namespace UltraTechInnovations\SocialFollow\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class GetFollowingsRelationshipAction
{
    public function execute(Model $follower): HasMany
    {
        $cacheKey = $this->getCacheKey('relationship', $follower);
        $cacheTtl = config('social-follow.follow.cache.ttl');

        return Cache::memo()->remember($cacheKey, $cacheTtl, function () use ($follower) {
            return $follower->hasMany(
                config('social-follow.models.follow'),
                'follower_id',
                $follower->getKeyName()
            )->where('follower_type', $follower->getMorphClass());
        });
    }

    protected function getCacheKey(string $type, Model $model): string
    {
        $prefix = config('social-follow.follow.cache.prefix');

        return "{$prefix}:{$type}:{$model->getKey()}";
    }
}
