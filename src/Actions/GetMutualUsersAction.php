<?php

declare(strict_types=1);

namespace UltraTechInnovations\SocialFollow\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use UltraTechInnovations\SocialFollow\Models\Follow;

final class GetMutualUsersAction
{
    public function execute(Model $user1, Model $user2): Collection
    {
        if (!config('social-follow.follow.cache.enabled')) {
            return $this->getFreshMutualUsers($user1, $user2);
        }

        $cacheKey = $this->getCacheKey($user1, $user2);
        $ttl = config('social-follow.follow.cache.ttl', 86400);

        return Cache::memo()->remember($cacheKey, $ttl, function() use ($user1, $user2) {
            return $this->getFreshMutualUsers($user1, $user2);
        });
    }

    protected function getFreshMutualUsers(Model $user1, Model $user2): Collection
    {
        // Get users that both $user1 and $user2 follow
        $user1Followings = Follow::where('follower_id', $user1->getKey())
            ->where('follower_type', $user1->getMorphClass())
            ->accepted()
            ->pluck('followable_id', 'followable_type');

        $user2Followings = Follow::where('follower_id', $user2->getKey())
            ->where('follower_type', $user2->getMorphClass())
            ->accepted()
            ->pluck('followable_id', 'followable_type');

        // Find intersection of followed users
        $mutualUserIds = [];
        foreach ($user1Followings as $type => $ids) {
            if (isset($user2Followings[$type])) {
                $mutualUserIds[$type] = array_intersect(
                    $ids->toArray(),
                    $user2Followings[$type]->toArray()
                );
            }
        }

        // Load mutual users with their types
        $mutualUsers = new Collection();
        foreach ($mutualUserIds as $type => $ids) {
            $typeUsers = app($type)->whereIn('id', $ids)->get();
            $mutualUsers = $mutualUsers->merge($typeUsers);
        }

        return $mutualUsers;
    }

    protected function getCacheKey(Model $user1, Model $user2): string
    {
        $prefix = config('social-follow.follow.cache.prefix', 'social_follow');
        $id1 = $user1->getKey();
        $id2 = $user2->getKey();

        // Sort IDs to ensure cache key consistency regardless of parameter order
        $sortedIds = [$id1, $id2];
        sort($sortedIds);

        return sprintf('%s:mutual:%s:%s:%s:%s',
            $prefix,
            $user1->getMorphClass(),
            $sortedIds[0],
            $user2->getMorphClass(),
            $sortedIds[1]
        );
    }

    public static function invalidateCache(Model $user1, Model $user2): void
    {
        if (!config('social-follow.follow.cache.enabled')) {
            return;
        }

        $prefix = config('social-follow.follow.cache.prefix', 'social_follow');
        $id1 = $user1->getKey();
        $id2 = $user2->getKey();

        // Invalidate both possible key orders
        Cache::forget(sprintf('%s:mutual:%s:%s:%s:%s',
            $prefix,
            $user1->getMorphClass(),
            $id1,
            $user2->getMorphClass(),
            $id2
        ));

        Cache::forget(sprintf('%s:mutual:%s:%s:%s:%s',
            $prefix,
            $user2->getMorphClass(),
            $id2,
            $user1->getMorphClass(),
            $id1
        ));
    }
}
