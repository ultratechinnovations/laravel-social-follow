<?php

namespace UltraTechInnovations\SocialFollow\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CheckFollowRequestAction
{
    public function execute(Model $follower, Model $followable): bool
    {
        if (! config('social-follow.approval_required')) {
            return false;
        }

        if (! config('social-follow.follow.cache.enabled')) {
            return $this->getFreshRequestStatus($follower, $followable);
        }

        $cacheKey = $this->getCacheKey($follower, $followable, 'request');
        $cacheTtl = config('social-follow.follow.cache.ttl');

        return Cache::memo()->remember($cacheKey, $cacheTtl, function () use ($follower, $followable) {
            return $this->getFreshRequestStatus($follower, $followable);
        });
    }

    protected function getFreshRequestStatus(Model $follower, Model $followable): bool
    {
        return $follower->followings()
            ->where('followable_id', $followable->getKey())
            ->where('followable_type', $followable->getMorphClass())
            ->pending()
            ->exists();
    }

    protected function getCacheKey(Model $follower, Model $followable, string $type): string
    {
        $prefix = config('social-follow.follow.cache.prefix');

        return "{$prefix}:{$type}:{$follower->getKey()}:{$followable->getMorphClass()}:{$followable->getKey()}";
    }
}
