<?php

declare(strict_types=1);

namespace UltraTechInnovations\SocialFollow\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use UltraTechInnovations\SocialFollow\Actions\GetFollowersAction;

trait CanBeFollowed
{
    public function followers(): HasMany
    {
        return $this->hasMany(
            config('social-follow.models.follow'),
            'followable_id',
            $this->getKeyName()
        )->where('followable_type', $this->getMorphClass());
    }

    public function followerCount(): int
    {
        return $this->followers()
            ->when(config('social-follow.approval_required'), fn ($query) => $query->accepted())
            ->count();
    }

    public function isFollowedBy(Model $follower): bool
    {
        return $this->followers()
            ->where('follower_id', $follower->getKey())
            ->where('follower_type', $follower->getMorphClass())
            ->when(config('social-follow.approval_required'), fn ($query) => $query->accepted())
            ->exists();
    }

    public function getFollowers(): Collection
    {
        return app(GetFollowersAction::class)->execute($this);
    }

    public function pendingFollowers(): Collection
    {
        if (! config('social-follow.approval_required')) {
            return new Collection;
        }

        return $this->followers()->pending()->get();
    }
}
