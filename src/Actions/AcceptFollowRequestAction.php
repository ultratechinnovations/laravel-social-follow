<?php

declare(strict_types=1);

namespace UltraTechInnovations\SocialFollow\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use UltraTechInnovations\SocialFollow\Models\Follow;
use UltraTechInnovations\SocialFollow\Notifications\FollowAcceptedNotification;

final class AcceptFollowRequestAction
{
    public function execute(Model $follower, Model $followable): bool
    {

        $result = DB::transaction(function () use ($follower, $followable) {
            $follow = Follow::where([
                'follower_id' => $follower->getKey(),
                'follower_type' => $follower->getMorphClass(),
                'followable_id' => $followable->getKey(),
                'followable_type' => $followable->getMorphClass(),
            ])->pending()->first();

            if (! $follow) {
                return false;
            }

            $follow->update(['accepted_at' => now()]);

            return true;
        });

        if ($result && config('social-follow.follow.cache.enabled')) {
            $this->invalidateCache($follower, $followable);
        }

        if ($result && config('social-follow.notifications.enabled')) {
            $follower->notify(new FollowAcceptedNotification($followable));
        }

        return $result;
    }

    protected function invalidateCache(Model $follower, Model $followable): void
    {
        $prefix = config('social-follow.follow.cache.prefix');

        // Invalidate status caches
        Cache::forget("{$prefix}:status:{$follower->getKey()}:{$followable->getMorphClass()}:{$followable->getKey()}");
        Cache::forget("{$prefix}:request:{$follower->getKey()}:{$followable->getMorphClass()}:{$followable->getKey()}");

        // Invalidate counts
        Cache::forget("{$prefix}:count:{$follower->getKey()}");
        Cache::forget("{$prefix}:count:{$followable->getKey()}");

        // If you have follower counts cached
        Cache::forget("{$prefix}:followers_count:{$followable->getKey()}");
    }
}
