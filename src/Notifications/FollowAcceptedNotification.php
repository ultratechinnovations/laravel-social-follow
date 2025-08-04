<?php

namespace UltraTechInnovations\SocialFollow\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class FollowAcceptedNotification extends Notification
{
    public function __construct(public $whoAccepted) {}

    public function via($notifiable): array
    {
        $channels = config('social-follow.notifications.channels') ?? [];

        return $channels;
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'accepted_by_id' => $this->whoAccepted->id,
            'accepted_by_name' => $this->whoAccepted->name,
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'accepted_by_id' => $this->whoAccepted->id,
            'accepted_by_type' => get_class($this->whoAccepted),
        ];
    }
}
