<?php

namespace UltraTechInnovations\SocialFollow\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class GetFollowingCountAction
{
    public function execute(Model $follower): int
    {
        if (! config('social-follow.follow.cache.enabled')) {
            return $this->getFreshCount($follower);
        }

        $cacheKey = $this->getCacheKey('count', $follower);
        $cacheTtl = config('social-follow.follow.cache.ttl');

        return Cache::memo()->remember($cacheKey, $cacheTtl, function () use ($follower) {
            return $this->getFreshCount($follower);
        });
    }

    protected function getFreshCount(Model $follower): int
    {
        return $follower->followings()
            ->when(config('social-follow.approval_required'), fn ($query) => $query->accepted())
            ->count();
    }

    protected function getCacheKey(string $type, Model $model): string
    {
        $prefix = config('social-follow.follow.cache.prefix');

        return "{$prefix}:{$type}:{$model->getKey()}";
    }
}
