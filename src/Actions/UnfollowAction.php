<?php

declare(strict_types=1);

namespace UltraTechInnovations\SocialFollow\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use UltraTechInnovations\SocialFollow\Models\Follow;

class UnfollowAction
{
    public function execute(Model $follower, Model $followable): bool
    {
        return DB::transaction(function () use ($follower, $followable) {
            $follow = $this->getFollowRelationship($follower, $followable);

            if (! $follow) {
                return false;
            }

            $deleted = $follow->delete();

            if ($deleted) {
                $this->invalidateFollowCaches($follower, $followable);
            }

            return $deleted;
        });
    }

    protected function getFollowRelationship(Model $follower, Model $followable): ?Follow
    {
        if (! config('social-follow.follow.cache.enabled')) {
            return Follow::where([
                'follower_id' => $follower->getKey(),
                'follower_type' => $follower->getMorphClass(),
                'followable_id' => $followable->getKey(),
                'followable_type' => $followable->getMorphClass(),
            ])->first();
        }

        $cacheKey = $this->getFollowCacheKey($follower, $followable);
        $ttl = config('social-follow.follow.cache.ttl', 86400);

        return Cache::memo()->remember($cacheKey, $ttl, function () use ($follower, $followable) {
            return Follow::where([
                'follower_id' => $follower->getKey(),
                'follower_type' => $follower->getMorphClass(),
                'followable_id' => $followable->getKey(),
                'followable_type' => $followable->getMorphClass(),
            ])->first();
        });
    }

    protected function invalidateFollowCaches(Model $follower, Model $followable): void
    {
        if (! config('social-follow.follow.cache.enabled')) {
            return;
        }

        $prefix = config('social-follow.follow.cache.prefix', 'social_follow');

        // Invalidate relationship cache
        Cache::forget($this->getFollowCacheKey($follower, $followable));

        // Invalidate follower's following list and count
        Cache::forget("{$prefix}:following:{$follower->getMorphClass()}:{$follower->getKey()}");
        Cache::forget("{$prefix}:following_count:{$follower->getKey()}");

        // Invalidate followable's followers list and count
        Cache::forget("{$prefix}:followers:{$followable->getMorphClass()}:{$followable->getKey()}");
        Cache::forget("{$prefix}:followers_count:{$followable->getKey()}");

        // Invalidate status check caches
        Cache::forget("{$prefix}:status:{$follower->getKey()}:{$followable->getMorphClass()}:{$followable->getKey()}");
    }

    protected function getFollowCacheKey(Model $follower, Model $followable): string
    {
        $prefix = config('social-follow.follow.cache.prefix', 'social_follow');

        return sprintf('%s:relationship:%s:%s:%s:%s',
            $prefix,
            $follower->getKey(),
            $follower->getMorphClass(),
            $followable->getKey(),
            $followable->getMorphClass()
        );
    }

    public static function invalidateAllCaches(Model $follower, Model $followable): void
    {
        if (! config('social-follow.follow.cache.enabled')) {
            return;
        }

        $prefix = config('social-follow.follow.cache.prefix', 'social_follow');
        $baseKey = "{$prefix}:relationship:{$follower->getKey()}:{$follower->getMorphClass()}:{$followable->getKey()}:{$followable->getMorphClass()}";

        Cache::forget($baseKey);
        Cache::forget("{$prefix}:following:{$follower->getMorphClass()}:{$follower->getKey()}");
        Cache::forget("{$prefix}:following_count:{$follower->getKey()}");
        Cache::forget("{$prefix}:followers:{$followable->getMorphClass()}:{$followable->getKey()}");
        Cache::forget("{$prefix}:followers_count:{$followable->getKey()}");
        Cache::forget("{$prefix}:status:{$follower->getKey()}:{$followable->getMorphClass()}:{$followable->getKey()}");
    }
}
