<?php

namespace UltraTechInnovations\SocialFollow\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CheckFollowingStatusAction
{
    public function execute(Model $follower, Model $followable): bool
    {
        if (!config('social-follow.follow.cache.enabled')) {
            return $this->getFreshStatus($follower, $followable);
        }

        $cacheKey = $this->getCacheKey($follower, $followable, 'status');
        $cacheTtl = config('social-follow.follow.cache.ttl');

        return Cache::memo()->remember($cacheKey, $cacheTtl, function() use ($follower, $followable) {
            return $this->getFreshStatus($follower, $followable);
        });
    }

    protected function getFreshStatus(Model $follower, Model $followable): bool
    {
        return $follower->followings()
            ->where('followable_id', $followable->getKey())
            ->where('followable_type', $followable->getMorphClass())
            ->when(config('social-follow.approval_required'), fn ($query) => $query->accepted())
            ->exists();
    }

    protected function getCacheKey(Model $follower, Model $followable, string $type): string
    {
        $prefix = config('social-follow.follow.cache.prefix');
        return "{$prefix}:{$type}:{$follower->getKey()}:{$followable->getMorphClass()}:{$followable->getKey()}";
    }
}
