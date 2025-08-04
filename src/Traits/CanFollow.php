<?php

namespace UltraTechInnovations\SocialFollow\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use UltraTechInnovations\SocialFollow\Actions\AcceptFollowRequestAction;
use UltraTechInnovations\SocialFollow\Actions\CheckFollowingStatusAction;
use UltraTechInnovations\SocialFollow\Actions\CheckFollowRequestAction;
use UltraTechInnovations\SocialFollow\Actions\FollowAction;
use UltraTechInnovations\SocialFollow\Actions\GetFollowingCountAction;
use UltraTechInnovations\SocialFollow\Actions\GetFollowingsAction;
use UltraTechInnovations\SocialFollow\Actions\GetFollowingsRelationshipAction;
use UltraTechInnovations\SocialFollow\Actions\UnfollowAction;
use UltraTechInnovations\SocialFollow\Models\Follow;

trait CanFollow
{
    public function follow(Model $followable): Follow
    {
        $this->validateFollowable($followable);

        return app(FollowAction::class)->execute($this, $followable);
    }

    public function unfollow(Model $followable): bool
    {
        $this->validateFollowable($followable);

        return app(UnfollowAction::class)->execute($this, $followable);
    }

    public function toggleFollow($followable): Follow|bool
    {
        return $this->isFollowing($followable) ? $this->unfollow($followable) : $this->follow($followable);
    }

    public function isFollowing(Model $followable): bool
    {
        $this->validateFollowable($followable);

        return app(CheckFollowingStatusAction::class)->execute($this, $followable);
    }

    public function hasRequestedToFollow(Model $followable): bool
    {
        $this->validateFollowable($followable);

        return app(CheckFollowRequestAction::class)->execute($this, $followable);
    }

    public function acceptFollowRequest(Model $follower): bool
    {
        return app(AcceptFollowRequestAction::class)->execute($follower, $this);
    }

    public function getFollowings(): Collection
    {
        return app(GetFollowingsAction::class)->execute($this);
    }

    public function followingCount(): int
    {
        return app(GetFollowingCountAction::class)->execute($this);
    }

    public function followings(): HasMany
    {
        return app(GetFollowingsRelationshipAction::class)->execute($this);
    }

    protected function validateFollowable(Model $followable): void
    {
        if (! in_array(CanBeFollowed::class, class_uses_recursive($followable))) {
            throw new \InvalidArgumentException(
                sprintf('Model %s must use the CanBeFollowed trait', get_class($followable))
            );
        }
    }
}
