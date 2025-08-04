<?php

declare(strict_types=1);

namespace UltraTechInnovations\SocialFollow\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use UltraTechInnovations\SocialFollow\Models\Follow;
use UltraTechInnovations\SocialFollow\Notifications\FollowNotification;

class FollowAction
{
    public function execute(Model $follower, Model $followable): Follow
    {
        return DB::transaction(function () use ($follower, $followable) {
            $existingFollow = $this->getExistingFollow($follower, $followable);

            if ($existingFollow) {
                return $existingFollow;
            }

            $follow = Follow::create([
                'follower_id' => $follower->getKey(),
                'follower_type' => $follower->getMorphClass(),
                'followable_id' => $followable->getKey(),
                'followable_type' => $followable->getMorphClass(),
                'accepted_at' => config('social-follow.approval_required') ? null : now(),
            ]);

            $this->updateCacheAfterFollow($follower, $followable);

            if (config('social-follow.notifications.enabled')) {
                $requiresAcceptance = config('social-follow.approval_required');
                $followable->notify(new FollowNotification($follower, $requiresAcceptance));
            }

            return $follow;

        });
    }

    protected function getExistingFollow(Model $follower, Model $followable): ?Follow
    {
        $cacheKey = $this->getFollowCacheKey($follower, $followable);

        if (config('social-follow.follow.cache.enabled')) {
            return Cache::memo()->remember($cacheKey,
                config('social-follow.follow.cache.ttl'),
                fn () => Follow::where([
                    'follower_id' => $follower->getKey(),
                    'follower_type' => $follower->getMorphClass(),
                    'followable_id' => $followable->getKey(),
                    'followable_type' => $followable->getMorphClass(),
                ])->first()
            );
        }

        return Follow::where([
            'follower_id' => $follower->getKey(),
            'follower_type' => $follower->getMorphClass(),
            'followable_id' => $followable->getKey(),
            'followable_type' => $followable->getMorphClass(),
        ])->first();
    }

    protected function updateCacheAfterFollow(Model $follower, Model $followable): void
    {
        if (! config('social-follow.follow.cache.enabled')) {
            return;
        }

        $prefix = config('social-follow.follow.cache.prefix');

        // Cache the new follow relationship
        Cache::put(
            $this->getFollowCacheKey($follower, $followable),
            true,
            config('social-follow.follow.cache.ttl')
        );

        // Invalidate counts
        Cache::forget("{$prefix}:count:following:{$follower->getKey()}");
        Cache::forget("{$prefix}:count:followers:{$followable->getKey()}");

        // Invalidate lists if cached
        Cache::forget("{$prefix}:list:following:{$follower->getKey()}");
        Cache::forget("{$prefix}:list:followers:{$followable->getKey()}");
    }

    protected function getFollowCacheKey(Model $follower, Model $followable): string
    {
        $prefix = config('social-follow.follow.cache.prefix');

        return sprintf('%s:follow:%s:%s:%s:%s',
            $prefix,
            $follower->getKey(),
            $follower->getMorphClass(),
            $followable->getKey(),
            $followable->getMorphClass()
        );
    }
}
