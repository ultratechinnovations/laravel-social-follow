<?php

declare(strict_types=1);

namespace UltraTechInnovations\SocialFollow\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use UltraTechInnovations\SocialFollow\Models\Block;

final class UnblockAction
{
    public function execute(Model $blocker, Model $blockable): bool
    {
        return DB::transaction(function () use ($blocker, $blockable) {
            $block = $this->getBlock($blocker, $blockable);

            if (!$block) {
                return false;
            }

            $deleted = $block->delete();

            if ($deleted) {
                $this->invalidateBlockCaches($blocker, $blockable);
            }

            return $deleted;
        });
    }

    protected function getBlock(Model $blocker, Model $blockable): ?Block
    {
        if (!config('social-follow.block.cache.enabled', false)) {
            return Block::where([
                'blocker_id' => $blocker->getKey(),
                'blocker_type' => $blocker->getMorphClass(),
                'blockable_id' => $blockable->getKey(),
                'blockable_type' => $blockable->getMorphClass(),
            ])->first();
        }

        $cacheKey = $this->getBlockCacheKey($blocker, $blockable);
        $ttl = config('social-follow.block.cache.ttl', 86400);

        return Cache::memo()->remember($cacheKey, $ttl, function() use ($blocker, $blockable) {
            return Block::where([
                'blocker_id' => $blocker->getKey(),
                'blocker_type' => $blocker->getMorphClass(),
                'blockable_id' => $blockable->getKey(),
                'blockable_type' => $blockable->getMorphClass(),
            ])->first();
        });
    }

    protected function invalidateBlockCaches(Model $blocker, Model $blockable): void
    {
        if (!config('social-follow.block.cache.enabled', false)) {
            return;
        }

        $prefix = config('social-follow.block.cache.prefix', 'social_block');

        // Invalidate the block relationship cache
        Cache::forget($this->getBlockCacheKey($blocker, $blockable));

        // Invalidate blocker's block list cache
        Cache::forget("{$prefix}:blocker:{$blocker->getKey()}");

        // Invalidate blockable's blocked_by cache
        Cache::forget("{$prefix}:blockable:{$blockable->getKey()}");

        // Invalidate counts if cached
        Cache::forget("{$prefix}:count:blocker:{$blocker->getKey()}");
        Cache::forget("{$prefix}:count:blockable:{$blockable->getKey()}");
    }

    protected function getBlockCacheKey(Model $blocker, Model $blockable): string
    {
        $prefix = config('social-follow.block.cache.prefix', 'social_block');
        return sprintf('%s:block:%s:%s:%s:%s',
            $prefix,
            $blocker->getKey(),
            $blocker->getMorphClass(),
            $blockable->getKey(),
            $blockable->getMorphClass()
        );
    }

    /**
     * Static method to invalidate all block-related caches
     */
    public static function invalidateAllCaches(Model $blocker, Model $blockable): void
    {
        if (!config('social-follow.block.cache.enabled', false)) {
            return;
        }

        $prefix = config('social-follow.block.cache.prefix', 'social_block');

        // Invalidate all possible variations
        Cache::forget(sprintf('%s:block:%s:%s:%s:%s',
            $prefix,
            $blocker->getKey(),
            $blocker->getMorphClass(),
            $blockable->getKey(),
            $blockable->getMorphClass()
        ));

        Cache::forget("{$prefix}:blocker:{$blocker->getKey()}");
        Cache::forget("{$prefix}:blockable:{$blockable->getKey()}");
        Cache::forget("{$prefix}:count:blocker:{$blocker->getKey()}");
        Cache::forget("{$prefix}:count:blockable:{$blockable->getKey()}");
    }
}
