<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Tests\TestModels\InvalidModel;
use Tests\TestModels\Page;
use Tests\TestModels\User;
use UltraTechInnovations\SocialFollow\Models\Follow;

beforeEach(function () {
    // Disable cache for testing
    config(['cache.default' => 'array']);

    // Setup tables
    Schema::dropIfExists('test_follows');
    Schema::dropIfExists('test_users');
    Schema::dropIfExists('test_pages');
    Schema::dropIfExists('test_invalid_models');

    // Setup tables
    if (! Schema::hasTable('test_users')) {
        Schema::create('test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('test_pages')) {
        Schema::create('test_pages', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('test_follows')) {
        Schema::create('test_follows', function ($table) {
            $table->id();
            $table->unsignedBigInteger('follower_id');
            $table->string('follower_type');
            $table->unsignedBigInteger('followable_id');
            $table->string('followable_type');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    config(['social-follow.table_prefix' => 'test_']);
});

afterEach(function () {
    Schema::dropIfExists('test_follows');
    Schema::dropIfExists('test_users');
    Schema::dropIfExists('test_pages');
});

it('can follow another model', function () {
    $user = User::create(['name' => 'Follower']);
    $page = Page::create(['name' => 'Page to follow']);

    $follow = $user->follow($page);

    expect($follow)->toBeInstanceOf(Follow::class)
        ->and($follow->follower_id)->toBe($user->id)
        ->and($follow->follower_type)->toBe(User::class)
        ->and($follow->followable_id)->toBe($page->id)
        ->and($follow->followable_type)->toBe(Page::class);
});

it('throws exception when trying to follow invalid model', function () {
    $user = User::create(['name' => 'Follower']);
    $invalid = new InvalidModel;

    $this->expectException(\InvalidArgumentException::class);
    $user->follow($invalid);
});

it('can unfollow a model', function () {
    $user = User::create(['name' => 'Follower']);
    $page = Page::create(['name' => 'Page to follow']);
    $user->follow($page);

    $result = $user->unfollow($page);

    expect($result)->toBeTrue()
        ->and($user->isFollowing($page))->toBeFalse();
});

it('returns false when unfollowing not followed model', function () {
    $user = User::create(['name' => 'Follower']);
    $page = Page::create(['name' => 'Page not followed']);

    $result = $user->unfollow($page);

    expect($result)->toBeFalse();
});

it('can toggle follow status', function () {

    config([
        'social-follow.follow.cache.enabled' => false,
    ]);

    $user = User::create(['name' => 'Follower']);
    $page = Page::create(['name' => 'Page to follow']);

    // First toggle should follow
    $firstResult = $user->toggleFollow($page);
    expect($firstResult)->toBeInstanceOf(Follow::class)
        ->and($user->isFollowing($page))->toBeTrue();

    // Second toggle should unfollow
    $secondResult = $user->toggleFollow($page);
    expect($secondResult)->toBeTrue()
        ->and($user->isFollowing($page))->toBeFalse();
});

it('can check if following a model', function () {

    config([
        'social-follow.follow.cache.enabled' => false,
    ]);

    $user = User::create(['name' => 'Follower']);
    $page = Page::create(['name' => 'Page to follow']);

    // Verify not following initially
    expect($user->isFollowing($page))->toBeFalse();

    // Create the follow relationship
    $follow = $user->follow($page);

    // Verify the follow was created
    expect($follow)->toBeInstanceOf(Follow::class);

    // Verify isFollowing returns true
    expect($user->isFollowing($page))->toBeTrue()
        ->and(Follow::where([
            'follower_id' => $user->id,
            'follower_type' => User::class,
            'followable_id' => $page->id,
            'followable_type' => Page::class,
        ])->exists())->toBeTrue(); // Double check DB
});

it('can check follow request status', function () {
    // Setup required configuration
    config([
        'social-follow.approval_required' => true,
        'social-follow.follow.cache.enabled' => false,
    ]);

    $user = User::create(['name' => 'Follower']);
    $page = Page::create(['name' => 'Page to follow']);

    // Verify no request exists initially
    expect($user->hasRequestedToFollow($page))->toBeFalse();

    // Create a follow request (should be pending since approval is required)
    $follow = $user->follow($page);

    // Verify the follow was created as pending
    expect($follow->accepted_at)->toBeNull();

    // Verify the request status
    expect($user->hasRequestedToFollow($page))->toBeTrue()
        // Additional verification
        ->and(Follow::where([
            'follower_id' => $user->id,
            'follower_type' => User::class,
            'followable_id' => $page->id,
            'followable_type' => Page::class,
            'accepted_at' => null, // Must be pending
        ])->exists())->toBeTrue();
});

it('can accept follow request', function () {

    $follower = User::create(['name' => 'Follower']);
    $user = User::create(['name' => 'User']);

    // Create a pending follow request
    $follow = Follow::create([
        'follower_id' => $follower->id,
        'follower_type' => get_class($follower),
        'followable_id' => $user->id,
        'followable_type' => get_class($user),
        'accepted_at' => null, // Explicitly set as pending
    ]);

    // Verify the follow is pending
    expect($follow->accepted_at)->toBeNull();

    // Accept the follow request
    $result = $user->acceptFollowRequest($follower);

    // Refresh the model
    $follow = $follow->fresh();

    expect($result)->toBeTrue()
        ->and($follow->accepted_at)->not->toBeNull()
        ->and($follow->accepted_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('can get followings', function () {
    $user = User::create(['name' => 'Follower']);
    $page1 = Page::create(['name' => 'Page 1']);
    $page2 = Page::create(['name' => 'Page 2']);

    $user->follow($page1);
    $user->follow($page2);

    $followings = $user->getFollowings();

    expect($followings)->toBeInstanceOf(Collection::class)
        ->and($followings)->toHaveCount(2);
});

it('can get following count', function () {
    $user = User::create(['name' => 'Follower']);
    $page1 = Page::create(['name' => 'Page 1']);
    $page2 = Page::create(['name' => 'Page 2']);

    $user->follow($page1);
    $user->follow($page2);

    expect($user->followingCount())->toBe(2);
});

it('can get followings relationship', function () {
    $user = User::create(['name' => 'Follower']);

    $relationship = $user->followings();

    expect($relationship)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class)
        ->and($relationship->getModel())->toBeInstanceOf(Follow::class);
});
