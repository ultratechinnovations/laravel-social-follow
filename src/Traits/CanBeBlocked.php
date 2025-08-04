<?php

declare(strict_types=1);

namespace UltraTechInnovations\SocialFollow\Traits;

use Illuminate\Database\Eloquent\Model;
use UltraTechInnovations\SocialFollow\Models\Block;

trait CanBeBlocked
{
    /**
     * Relationship for models that have blocked this model
     */
    public function blockers(): MorphMany
    {
        return $this->morphMany(Block::class, 'blockable');
    }

    /**
     * Check if this model is blocked by another model
     */
    public function isBlockedBy(Model $user)
    {
        if (! method_exists($blocker, 'hasBlocked')) {
            return false;
        }

        return Block::where([
            'blocker_id' => $blocker->getKey(),
            'blocker_type' => $blocker->getMorphClass(),
            'blockable_id' => $this->getKey(),
            'blockable_type' => $this->getMorphClass(),
        ])->exists();
    }

    /**
     * Get all models that have blocked this model
     */
    public function getBlockers()
    {
        return $this->blockers()->get()->map(function ($block) {
            return $block->blocker;
        })->filter();
    }

    /**
     * Get count of models that have blocked this model
     */
    public function blockerCount(): int
    {
        return $this->blockers()->count();
    }
}
