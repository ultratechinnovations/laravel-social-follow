<?php

namespace Tests\Unit\Notifications;

use Tests\TestModels\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use UltraTechInnovations\SocialFollow\Notifications\FollowNotification;

beforeEach(function () {

    Schema::dropIfExists('test_users');
    Schema::dropIfExists('notifications');

    // Setup tables
    if (!Schema::hasTable('test_users')) {
        Schema::create('test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }
    if(!Schema::hasTable('notifications')) {
        Schema::create('notifications', function ($table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    config(['social-follow.table_prefix' => 'test_']);

    $this->follower = new User(['id' => 1, 'name' => 'Test Follower']);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
    Schema::dropIfExists('notifications');
});

it('initializes with follower and acceptance flag', function () {
    $notification = new FollowNotification($this->follower, true);

    expect($notification->follower)->toBe($this->follower)
        ->and($notification->requiresAcceptance)->toBeTrue();
});

it('uses default notification channels when none configured', function () {
    Config::set('social-follow.notifications.channels', null);

    $notification = new FollowNotification($this->follower);
    expect($notification->via(new User()))->toBe([]);
});

it('uses configured notification channels', function () {
    Config::set('social-follow.notifications.channels', ['database', 'broadcast']);

    $notification = new FollowNotification($this->follower);
    expect($notification->via(new User()))->toBe(['database', 'broadcast']);
});

it('generates correct broadcast message', function () {
    $notification = new FollowNotification($this->follower, true);
    $message = $notification->toBroadcast(new User());

    expect($message)->toBeInstanceOf(BroadcastMessage::class)
        ->and($message->data)->toMatchArray([
            'follower_id' => $this->follower->id,
            'follower_name' => $this->follower->name,
            'requires_acceptance' => true,
            'created_at' => now()->toDateTimeString(),
        ]);
});

it('generates correct array representation', function () {
    $notification = new FollowNotification($this->follower, false);
    $array = $notification->toArray(new User());

    expect($array)->toMatchArray([
        'follower_id' => $this->follower->id,
        'follower_type' => User::class,
        'requires_acceptance' => false,
    ]);
});

it('handles different notifiable types', function () {
    $page = new class extends \Illuminate\Database\Eloquent\Model {
        protected $table = 'pages';
    };

    $notification = new FollowNotification($this->follower);
    $array = $notification->toArray($page);

    expect($array['follower_type'])->toBe(User::class);
});

it('includes current timestamp in broadcast', function () {
    $now = now();
    $this->travelTo($now);

    $notification = new FollowNotification($this->follower);
    $message = $notification->toBroadcast(new User());

    expect($message->data['created_at'])->toBe($now->toDateTimeString());
});

it('shows correct acceptance status in output', function () {
    // Test with acceptance required
    $notification1 = new FollowNotification($this->follower, true);
    expect($notification1->toArray(new User())['requires_acceptance'])->toBeTrue();

    // Test without acceptance required
    $notification2 = new FollowNotification($this->follower, false);
    expect($notification2->toBroadcast(new User())->data['requires_acceptance'])->toBeFalse();
});

it('sends notification to notifiable', function () {
    Notification::fake();

    $user = User::create(['name' => 'Test User']);
    $follower = User::create(['name' => 'Follower']);

    $user->notify(new FollowNotification($follower));

    Notification::assertSentTo(
        $user,
        FollowNotification::class,
        function ($notification) use ($follower) {
            return $notification->follower->id === $follower->id;
        }
    );
});

it('stores notification in database', function () {
    config(['social-follow.notifications.channels' => ['database']]);

    $user = User::create(['name' => 'Test User']);
    $follower = User::create(['name' => 'Follower']);

    $user->notify(new FollowNotification($follower));

    $notification = $user->notifications()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->data)->toMatchArray([
            'follower_id' => $follower->id,
            'follower_type' => User::class,
            'requires_acceptance' => false,
        ]);
});
