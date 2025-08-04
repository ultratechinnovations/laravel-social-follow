<?php

declare(strict_types=1);

namespace UltraTechInnovations\SocialFollow\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use UltraTechInnovations\SocialFollow\Models\Follow;

final class GetFollowingsAction
{
    public function execute(
        Model $follower,
        bool $acceptedOnly = true,
        ?string $followableType = null
    ): Collection {
        if (! config('social-follow.follow.cache.enabled')) {
            return $this->getFreshFollowings($follower, $acceptedOnly, $followableType);
        }

        $cacheKey = $this->getFollowingsCacheKey($follower, $acceptedOnly, $followableType);
        $ttl = config('social-follow.follow.cache.ttl', 86400);

        return Cache::memo()->remember($cacheKey, $ttl, function () use ($follower, $acceptedOnly, $followableType) {
            return $this->getFreshFollowings($follower, $acceptedOnly, $followableType);
        });
    }

    public function count(
        Model $follower,
        bool $acceptedOnly = true,
        ?string $followableType = null
    ): int {
        if (! config('social-follow.follow.cache.enabled')) {
            return $this->getFreshFollowingCount($follower, $acceptedOnly, $followableType);
        }

        $cacheKey = $this->getFollowingCountCacheKey($follower, $acceptedOnly, $followableType);
        $ttl = config('social-follow.follow.cache.ttl', 86400);

        return Cache::memo()->remember($cacheKey, $ttl, function () use ($follower, $acceptedOnly, $followableType) {
            return $this->getFreshFollowingCount($follower, $acceptedOnly, $followableType);
        });
    }

    public function groupedByType(Model $follower, bool $acceptedOnly = true): array
    {
        if (! config('social-follow.follow.cache.enabled')) {
            return $this->getFreshGroupedFollowings($follower, $acceptedOnly);
        }

        $cacheKey = $this->getGroupedFollowingsCacheKey($follower, $acceptedOnly);
        $ttl = config('social-follow.follow.cache.ttl', 86400);

        return Cache::memo()->remember($cacheKey, $ttl, function () use ($follower, $acceptedOnly) {
            return $this->getFreshGroupedFollowings($follower, $acceptedOnly);
        });
    }

    protected function getFreshFollowings(
        Model $follower,
        bool $acceptedOnly,
        ?string $followableType
    ): Collection {
        $query = Follow::where('follower_id', $follower->getKey())
            ->where('follower_type', $follower->getMorphClass())
            ->with('followable');

        if ($acceptedOnly) {
            $query->accepted();
        }

        if ($followableType) {
            $query->where('followable_type', $followableType);
        }

        return $query->get()
            ->map(function (Follow $follow) {
                return $follow->followable;
            })
            ->filter();
    }

    protected function getFreshFollowingCount(
        Model $follower,
        bool $acceptedOnly,
        ?string $followableType
    ): int {
        $query = Follow::where('follower_id', $follower->getKey())
            ->where('follower_type', $follower->getMorphClass());

        if ($acceptedOnly) {
            $query->accepted();
        }

        if ($followableType) {
            $query->where('followable_type', $followableType);
        }

        return $query->count();
    }

    protected function getFreshGroupedFollowings(Model $follower, bool $acceptedOnly): array
    {
        $query = Follow::where('follower_id', $follower->getKey())
            ->where('follower_type', $follower->getMorphClass())
            ->with('followable');

        if ($acceptedOnly) {
            $query->accepted();
        }

        return $query->get()
            ->groupBy('followable_type')
            ->map(function ($typeFollows) {
                return $typeFollows->map(function (Follow $follow) {
                    return $follow->followable;
                })->filter();
            })
            ->toArray();
    }

    protected function getFollowingsCacheKey(
        Model $follower,
        bool $acceptedOnly,
        ?string $followableType
    ): string {
        $prefix = config('social-follow.follow.cache.prefix', 'social_follow');
        $status = $acceptedOnly ? 'accepted' : 'all';
        $type = $followableType ?: 'all_types';

        return "{$prefix}:followings:{$follower->getMorphClass()}:{$follower->getKey()}:{$status}:{$type}";
    }

    protected function getFollowingCountCacheKey(
        Model $follower,
        bool $acceptedOnly,
        ?string $followableType
    ): string {
        $prefix = config('social-follow.follow.cache.prefix', 'social_follow');
        $status = $acceptedOnly ? 'accepted' : 'all';
        $type = $followableType ?: 'all_types';

        return "{$prefix}:followings_count:{$follower->getMorphClass()}:{$follower->getKey()}:{$status}:{$type}";
    }

    protected function getGroupedFollowingsCacheKey(Model $follower, bool $acceptedOnly): string
    {
        $prefix = config('social-follow.follow.cache.prefix', 'social_follow');
        $status = $acceptedOnly ? 'accepted' : 'all';

        return "{$prefix}:grouped_followings:{$follower->getMorphClass()}:{$follower->getKey()}:{$status}";
    }

    public static function invalidateCache(Model $follower): void
    {
        if (! config('social-follow.follow.cache.enabled')) {
            return;
        }

        $prefix = config('social-follow.follow.cache.prefix', 'social_follow');
        $baseKey = "{$prefix}:followings:{$follower->getMorphClass()}:{$follower->getKey()}";

        // Invalidate all variations
        Cache::forget("{$baseKey}:accepted:all_types");
        Cache::forget("{$baseKey}:all:all_types");
        Cache::forget("{$baseKey}_count:accepted:all_types");
        Cache::forget("{$baseKey}_count:all:all_types");
        Cache::forget("{$prefix}:grouped_followings:{$follower->getMorphClass()}:{$follower->getKey()}:accepted");
        Cache::forget("{$prefix}:grouped_followings:{$follower->getMorphClass()}:{$follower->getKey()}:all");

        // Invalidate specific type caches (you might need to track these separately)
        // Could implement a more sophisticated invalidation if needed
    }
}
