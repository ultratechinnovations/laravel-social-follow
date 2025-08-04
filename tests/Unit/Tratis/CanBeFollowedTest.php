<?php

use UltraTechInnovations\SocialFollow\Traits\CanBeFollowed;
use UltraTechInnovations\SocialFollow\Traits\CanFollow;
use UltraTechInnovations\SocialFollow\Models\Follow;
use UltraTechInnovations\SocialFollow\Actions\GetFollowersAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestModels\User;
use Tests\TestModels\Page;
use Tests\TestModels\InvalidModel;

beforeEach(function () {
    // Setup configuration
    config([
        'cache.default' => 'array',
        'social-follow.table_prefix' => 'test_',
        'social-follow.models.follow' => Follow::class,
        'social-follow.cache.enabled' => false
    ]);

    // Create tables
    Schema::create('test_users', function ($table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('test_pages', function ($table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('test_follows', function ($table) {
        $table->id();
        $table->unsignedBigInteger('follower_id');
        $table->string('follower_type');
        $table->unsignedBigInteger('followable_id');
        $table->string('followable_type');
        $table->timestamp('accepted_at')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('test_follows');
    Schema::dropIfExists('test_users');
    Schema::dropIfExists('test_pages');
});

it('defines followers relationship', function () {
    $page = Page::create(['name' => 'Test Page']);

    $relationship = $page->followers();

    expect($relationship)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class)
        ->and($relationship->getModel())->toBeInstanceOf(Follow::class)
        ->and($relationship->getForeignKeyName())->toBe('followable_id')
        ->and($relationship->getQualifiedParentKeyName())->toBe('test_pages.id');
});

it('can count followers', function () {
    $user1 = User::create(['name' => 'Follower 1']);
    $user2 = User::create(['name' => 'Follower 2']);
    $page = Page::create(['name' => 'Test Page']);

    // Create follows (accepted by default)
    $user1->follow($page);
    $user2->follow($page);

    expect($page->followerCount())->toBe(2);
});

it('can count followers with approval required', function () {
    config(['social-follow.approval_required' => true]);

    $user1 = User::create(['name' => 'Follower 1']);
    $user2 = User::create(['name' => 'Follower 2']);
    $page = Page::create(['name' => 'Test Page']);

    // Create follows (pending by default)
    $follow1 = $user1->follow($page);
    $follow2 = $user2->follow($page);

    // Initially no accepted followers
    expect($page->followerCount())->toBe(0);

    // Accept one follow
    $follow1->update(['accepted_at' => now()]);
    expect($page->followerCount())->toBe(1);
});

it('can check if followed by specific user', function () {
    $user = User::create(['name' => 'Follower']);
    $page = Page::create(['name' => 'Test Page']);

    expect($page->isFollowedBy($user))->toBeFalse();

    $user->follow($page);
    expect($page->isFollowedBy($user))->toBeTrue();
});

it('can check if followed by specific user with approval required', function () {
    config(['social-follow.approval_required' => true]);

    $user = User::create(['name' => 'Follower']);
    $page = Page::create(['name' => 'Test Page']);

    $follow = $user->follow($page);
    expect($page->isFollowedBy($user))->toBeFalse();

    $follow->update(['accepted_at' => now()]);
    expect($page->isFollowedBy($user))->toBeTrue();
});

it('can get followers collection', function () {
    // Disable cache for testing
    config(['social-follow.follow.cache.enabled' => false]);

    $user1 = User::create(['name' => 'Follower 1']);
    $user2 = User::create(['name' => 'Follower 2']);
    $page = Page::create(['name' => 'Test Page']);

    // Create follows
    $user1->follow($page);
    $user2->follow($page);

    // Get followers
    $followers = $page->getFollowers();

    // Should return User models, not Follow models
    expect($followers)->toBeInstanceOf(Collection::class)
        ->and($followers)->toHaveCount(2)
        ->and($followers->first())->toBeInstanceOf(User::class)
        ->and($followers->pluck('id')->toArray())->toMatchArray([$user1->id, $user2->id]);
});

it('can get pending followers when approval required', function () {
    config([
        'social-follow.approval_required' => true,
        'social-follow.follow.cache.enabled' => false,
    ]);

    $user1 = User::create(['name' => 'Follower 1']);
    $user2 = User::create(['name' => 'Follower 2']);
    $page = Page::create(['name' => 'Test Page']);

    $follow1 = $user1->follow($page);
    $follow2 = $user2->follow($page);

    // Accept one follow
    $follow1->update(['accepted_at' => now()]);

    // Get pending followers (should exclude accepted follows)
    $pendingFollowers = $page->pendingFollowers();

    expect($pendingFollowers)->toBeInstanceOf(Collection::class)
        ->and($pendingFollowers)->toHaveCount(1)
        ->and($pendingFollowers->first()->id)->toBe($user2->id);
});

it('returns empty collection for pending followers when approval not required', function () {
    config(['social-follow.approval_required' => false]);

    $user = User::create(['name' => 'Follower']);
    $page = Page::create(['name' => 'Test Page']);

    $user->follow($page);

    $pendingFollowers = $page->pendingFollowers();

    expect($pendingFollowers)->toBeInstanceOf(Collection::class)
        ->and($pendingFollowers)->toBeEmpty();
});

it('returns only accepted followers when requested', function () {
    config([
        'social-follow.approval_required' => true,
        'social-follow.follow.cache.enabled' => false,
    ]);

    $user1 = User::create(['name' => 'Follower 1']);
    $user2 = User::create(['name' => 'Follower 2']);
    $page = Page::create(['name' => 'Test Page']);

    $follow1 = $user1->follow($page);
    $follow2 = $user2->follow($page);

    // Accept one follow
    $follow1->update(['accepted_at' => now()]);

    // Get accepted followers only
    $acceptedFollowers = $page->getFollowers();

    expect($acceptedFollowers)->toHaveCount(1)
        ->and($acceptedFollowers->first()->id)->toBe($user1->id);
});

it('can get all followers including pending when requested', function () {
    config([
        'social-follow.approval_required' => true,
        'social-follow.follow.cache.enabled' => false,
    ]);

    $user1 = User::create(['name' => 'Follower 1']);
    $user2 = User::create(['name' => 'Follower 2']);
    $page = Page::create(['name' => 'Test Page']);

    $follow1 = $user1->follow($page);
    $follow2 = $user2->follow($page);

    // Accept one follow
    $follow1->update(['accepted_at' => now()]);

    // Get all followers including pending
    $allFollowers = app(GetFollowersAction::class)->execute($page, false);

    expect($allFollowers)->toHaveCount(2)
        ->and($allFollowers->pluck('id')->toArray())->toMatchArray([$user1->id, $user2->id]);
});
