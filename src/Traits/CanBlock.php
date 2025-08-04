<?php

declare(strict_types=1);

namespace UltraTechInnovations\SocialFollow\Traits;

use Illuminate\Database\Eloquent\Model;
use UltraTechInnovations\SocialFollow\Actions\BlockAction;
use UltraTechInnovations\SocialFollow\Actions\UnblockAction;
use UltraTechInnovations\SocialFollow\Models\Block;

trait CanBlock
{
    public function block(Model $blockable)
    {
        return app(BlockAction::class)->execute($this, $blockable);
    }

    public function unblock(Model $blockable)
    {
        return app(UnblockAction::class)->execute($this, $followable);
    }

    public function hasBlocked(Model $blockable)
    {
        return Block::where([
            'blocker_id' => $this->getKey(),
            'blocker_type' => $this->getMorphClass(),
            'blockable_id' => $blockable->getKey(),
            'blockable_type' => $blockable->getMorphClass(),
        ])->exists();
    }
}
