<?php

declare(strict_types=1);

namespace UltraTechInnovations\SocialFollow\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use UltraTechInnovations\SocialFollow\Models\Block;

final class BlockAction
{
    public function execute(Model $blocker, Model $blockable): Block
    {
        return DB::transaction(function () use ($blocker, $blockable) {
            // Check if block already exists
            if ($this->blockExists($blocker, $blockable)) {
                throw new \Exception('Block relationship already exists');
            }

            // Create the block
            $block = Block::create([
                'blocker_id' => $blocker->getKey(),
                'blocker_type' => $blocker->getMorphClass(),
                'blockable_id' => $blockable->getKey(),
                'blockable_type' => $blockable->getMorphClass(),
            ]);

            $this->invalidateBlockCaches($blocker, $blockable);

            return $block;
        });
    }


    private function blockExists(Model $blocker, Model $blockable): bool
    {
        if (!config('social-follow.block.cache.enabled', false)) {
            return $this->freshBlockExists($blocker, $blockable);
        }

        $cacheKey = $this->getBlockCacheKey($blocker, $blockable);
        $ttl = config('social-follow.block.cache.ttl', 86400);

        return Cache::memo()->remember($cacheKey, $ttl, function() use ($blocker, $blockable) {
            return $this->freshBlockExists($blocker, $blockable);
        });
    }

    private function freshBlockExists(Model $blocker, Model $blockable): bool
    {
        return Block::where([
            'blocker_id' => $blocker->getKey(),
            'blocker_type' => $blocker->getMorphClass(),
            'blockable_id' => $blockable->getKey(),
            'blockable_type' => $blockable->getMorphClass(),
        ])->exists();
    }

    private function invalidateBlockCaches(Model $blocker, Model $blockable): void
    {
        if (!config('social-follow.block.cache.enabled', false)) {
            return;
        }

        $prefix = config('social-follow.block.cache.prefix', 'social_block');

        // Invalidate existence check cache
        Cache::forget($this->getBlockCacheKey($blocker, $blockable));

        // Invalidate blocker's block list cache
        Cache::forget("{$prefix}:blocker:{$blocker->getKey()}");

        // Invalidate blockable's blocked_by cache
        Cache::forget("{$prefix}:blockable:{$blockable->getKey()}");

        // Invalidate counts if cached
        Cache::forget("{$prefix}:count:blocker:{$blocker->getKey()}");
        Cache::forget("{$prefix}:count:blockable:{$blockable->getKey()}");
    }

    private function getBlockCacheKey(Model $blocker, Model $blockable): string
    {
        $prefix = config('social-follow.block.cache.prefix', 'social_block');
        return "{$prefix}:exists:{$blocker->getKey()}:{$blockable->getMorphClass()}:{$blockable->getKey()}";
    }
}
