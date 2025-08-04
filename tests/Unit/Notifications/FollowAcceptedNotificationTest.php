<?php

use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Schema;
use Tests\TestModels\User;
use UltraTechInnovations\SocialFollow\Notifications\FollowAcceptedNotification;

beforeEach(function () {
    Schema::dropIfExists('test_users');
    Schema::dropIfExists('notifications');

    // Setup tables
    if (! Schema::hasTable('test_users')) {
        Schema::create('test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }
    if (! Schema::hasTable('notifications')) {
        Schema::create('notifications', function ($table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    $this->user = new User(['id' => 1, 'name' => 'Test User']);
    $this->notification = new FollowAcceptedNotification($this->user);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
    Schema::dropIfExists('notifications');
});

it('constructs with whoAccepted property', function () {
    expect($this->notification->whoAccepted)->toBe($this->user);
});

it('uses configured notification channels', function () {
    // Test with no channels configured
    config(['social-follow.notifications.channels' => null]);
    $notification = new FollowAcceptedNotification($this->user);
    expect($notification->via($this->user))->toBe([]);

    // Test with specific channels
    config(['social-follow.notifications.channels' => ['database', 'broadcast']]);
    $notification = new FollowAcceptedNotification($this->user);
    expect($notification->via($this->user))->toBe(['database', 'broadcast']);
});

it('generates correct broadcast message', function () {
    $message = $this->notification->toBroadcast($this->user);

    expect($message)->toBeInstanceOf(BroadcastMessage::class)
        ->and($message->data)->toBe([
            'accepted_by_id' => $this->user->id,
            'accepted_by_name' => $this->user->name,
            'created_at' => now()->toDateTimeString(),
        ]);
});

it('generates correct array representation', function () {
    $array = $this->notification->toArray($this->user);

    expect($array)->toBe([
        'accepted_by_id' => $this->user->id,
        'accepted_by_type' => User::class,
    ]);
});

it('handles different notifiable types', function () {
    $page = new class extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'pages';
    };

    $notification = new FollowAcceptedNotification($this->user);
    $array = $notification->toArray($page);

    expect($array['accepted_by_type'])->toBe(User::class);
});

it('broadcast message contains timestamp', function () {
    $now = now();
    $this->travelTo($now);

    $message = $this->notification->toBroadcast($this->user);

    expect($message->data['created_at'])->toBe($now->toDateTimeString());
});

it('sends notification through channels', function () {
    config(['social-follow.notifications.channels' => ['database']]);

    // Fake notifications and use mocks to avoid database
    Notification::fake();

    $user = User::create(['name' => 'Test User']);
    $userToNotify = User::create(['name' => 'Receiver']);

    $userToNotify->notify(new FollowAcceptedNotification($user));

    Notification::assertSentTo(
        $userToNotify,
        FollowAcceptedNotification::class,
        function ($notification, $channels) use ($user) {
            return in_array('database', $channels) &&
                   $notification->whoAccepted->id === $user->id;
        }
    );
});
