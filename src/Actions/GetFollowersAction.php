<?php

declare(strict_types=1);

namespace UltraTechInnovations\SocialFollow\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use UltraTechInnovations\SocialFollow\Models\Follow;

final class GetFollowersAction
{
    /**
     * Get all followers of a model
     *
     * @param  Model  $followable  The model being followed
     * @param  bool  $acceptedOnly  Whether to only include accepted follows (default: true)
     * @return Collection<int, Model> Collection of follower models
     */
    public function execute(Model $followable, bool $acceptedOnly = true): Collection
    {

        if (! config('social-follow.follow.cache.enabled')) {
            return $this->getFreshFollowers($followable, $acceptedOnly);
        }

        $cacheKey = $this->getFollowersCacheKey($followable, $acceptedOnly);
        $ttl = config('social-follow.follow.cache.ttl', 86400);

        return Cache::memo()->remember($cacheKey, $ttl, function () use ($followable, $acceptedOnly) {
            return $this->getFreshFollowers($followable, $acceptedOnly);
        });
    }

    /**
     * Get follower count for a model
     *
     * @param  Model  $followable  The model being followed
     * @param  bool  $acceptedOnly  Whether to only count accepted follows (default: true)
     */
    public function count(Model $followable, bool $acceptedOnly = true): int
    {
        if (! config('social-follow.follow.cache.enabled')) {
            return $this->getFreshFollowerCount($followable, $acceptedOnly);
        }

        $cacheKey = $this->getFollowerCountCacheKey($followable, $acceptedOnly);
        $ttl = config('social-follow.follow.cache.ttl', 86400);

        return Cache::memo()->remember($cacheKey, $ttl, function () use ($followable, $acceptedOnly) {
            return $this->getFreshFollowerCount($followable, $acceptedOnly);
        });
    }

    protected function getFreshFollowers(Model $followable, bool $acceptedOnly): Collection
    {
        $query = Follow::where('followable_id', $followable->getKey())
            ->where('followable_type', $followable->getMorphClass())
            ->with('follower');

        if ($acceptedOnly) {
            $query->accepted();
        }

        return $query->get()
            ->map(function (Follow $follow) {
                return $follow->follower;
            })
            ->filter();
    }

    protected function getFreshFollowerCount(Model $followable, bool $acceptedOnly): int
    {
        $query = Follow::where('followable_id', $followable->getKey())
            ->where('followable_type', $followable->getMorphClass());

        if ($acceptedOnly) {
            $query->accepted();
        }

        return $query->count();
    }

    protected function getFollowersCacheKey(Model $followable, bool $acceptedOnly): string
    {
        $prefix = config('social-follow.follow.cache.prefix', 'social_follow');
        $status = $acceptedOnly ? 'accepted' : 'all';

        return "{$prefix}:followers:{$followable->getMorphClass()}:{$followable->getKey()}:{$status}";
    }

    protected function getFollowerCountCacheKey(Model $followable, bool $acceptedOnly): string
    {
        $prefix = config('social-follow.follow.cache.prefix', 'social_follow');
        $status = $acceptedOnly ? 'accepted' : 'all';

        return "{$prefix}:followers_count:{$followable->getMorphClass()}:{$followable->getKey()}:{$status}";
    }

    /**
     * Invalidate followers cache for a followable
     */
    public static function invalidateCache(Model $followable): void
    {
        if (! config('social-follow.follow.cache.enabled')) {
            return;
        }

        $prefix = config('social-follow.follow.cache.prefix', 'social_follow');
        $baseKey = "{$prefix}:followers:{$followable->getMorphClass()}:{$followable->getKey()}";

        Cache::forget("{$baseKey}:accepted");
        Cache::forget("{$baseKey}:all");
        Cache::forget("{$baseKey}_count:accepted");
        Cache::forget("{$baseKey}_count:all");
    }
}
