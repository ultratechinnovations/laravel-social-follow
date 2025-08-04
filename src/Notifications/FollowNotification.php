<?php

namespace UltraTechInnovations\SocialFollow\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class FollowNotification extends Notification
{
    public function __construct(
        public $follower,
        public bool $requiresAcceptance = false
    ) {}

    public function via($notifiable): array
    {
        $channels = config('social-follow.notifications.channels') ?? [];

        return $channels;
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'follower_id' => $this->follower->id,
            'follower_name' => $this->follower->name,
            'requires_acceptance' => $this->requiresAcceptance,
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'follower_id' => $this->follower->id,
            'follower_type' => get_class($this->follower),
            'requires_acceptance' => $this->requiresAcceptance,
        ];
    }
}
