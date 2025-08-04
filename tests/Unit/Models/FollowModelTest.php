<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestModels\Page;
use Tests\TestModels\User;
use UltraTechInnovations\SocialFollow\Models\Follow;

beforeEach(function () {
    // Setup table prefix for testing
    config(['social-follow.table_prefix' => 'test_']);

    // Create tables with proper prefixed names
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
});

afterEach(function () {
    // Clean up tables after tests
    Schema::dropIfExists('test_follows');
    Schema::dropIfExists('test_users');
    Schema::dropIfExists('test_pages');
});

it('uses correct table name with prefix', function () {
    $follow = new Follow;

    expect($follow->getTable())->toBe('test_follows');
});

it('has correct fillable attributes', function () {
    $follow = new Follow;

    expect($follow->getFillable())->toBe([
        'follower_id',
        'follower_type',
        'followable_id',
        'followable_type',
        'accepted_at',
    ]);
});

it('has correct casts', function () {
    $follow = new Follow;

    expect($follow->getCasts())->toHaveKey('accepted_at', 'datetime');
});

it('can be created with valid attributes', function () {
    $follow = Follow::create([
        'follower_id' => 1,
        'follower_type' => User::class,
        'followable_id' => 2,
        'followable_type' => Page::class,
        'accepted_at' => now(),
    ]);

    expect($follow)->toBeInstanceOf(Follow::class)
        ->and($follow->follower_id)->toBe(1)
        ->and($follow->follower_type)->toBe(User::class)
        ->and($follow->followable_id)->toBe(2)
        ->and($follow->followable_type)->toBe(Page::class)
        ->and($follow->accepted_at)->toBeInstanceOf(Carbon::class);
});

it('has morphTo relationship for follower', function () {
    $follow = new Follow;

    expect($follow->follower())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class);
});

it('has morphTo relationship for followable', function () {
    $follow = new Follow;

    expect($follow->followable())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class);
});

it('can retrieve follower model', function () {
    $user = User::create(['name' => 'Test User']);

    $follow = Follow::create([
        'follower_id' => $user->id,
        'follower_type' => User::class,
        'followable_id' => 1,
        'followable_type' => Page::class,
    ]);

    expect($follow->follower)->toBeInstanceOf(User::class)
        ->and($follow->follower->id)->toBe($user->id);
});

it('can retrieve followable model', function () {
    $page = Page::create(['name' => 'Test Page']);

    $follow = Follow::create([
        'follower_id' => 1,
        'follower_type' => User::class,
        'followable_id' => $page->id,
        'followable_type' => Page::class,
    ]);

    expect($follow->followable)->toBeInstanceOf(Page::class)
        ->and($follow->followable->id)->toBe($page->id);
});

it('accepts null accepted_at for pending follows', function () {
    $follow = Follow::create([
        'follower_id' => 1,
        'follower_type' => User::class,
        'followable_id' => 2,
        'followable_type' => Page::class,
        'accepted_at' => null,
    ]);

    expect($follow->accepted_at)->toBeNull();
});

it('scope accepted filters accepted follows', function () {
    Follow::create(['follower_id' => 1, 'follower_type' => User::class, 'followable_id' => 2, 'followable_type' => Page::class, 'accepted_at' => now()]);
    Follow::create(['follower_id' => 3, 'follower_type' => User::class, 'followable_id' => 4, 'followable_type' => Page::class, 'accepted_at' => null]);

    $acceptedFollows = Follow::accepted()->get();

    expect($acceptedFollows)->toHaveCount(1)
        ->and($acceptedFollows->first()->accepted_at)->not->toBeNull();
});

it('scope pending filters pending follows', function () {
    Follow::create(['follower_id' => 1, 'follower_type' => User::class, 'followable_id' => 2, 'followable_type' => Page::class, 'accepted_at' => now()]);
    Follow::create(['follower_id' => 3, 'follower_type' => User::class, 'followable_id' => 4, 'followable_type' => Page::class, 'accepted_at' => null]);

    $pendingFollows = Follow::pending()->get();

    expect($pendingFollows)->toHaveCount(1)
        ->and($pendingFollows->first()->accepted_at)->toBeNull();
});

it('scope followerType filters by follower type', function () {
    Follow::create(['follower_id' => 1, 'follower_type' => User::class, 'followable_id' => 2, 'followable_type' => Page::class]);
    Follow::create(['follower_id' => 3, 'follower_type' => Page::class, 'followable_id' => 4, 'followable_type' => User::class]);

    $userFollows = Follow::followerType(User::class)->get();

    expect($userFollows)->toHaveCount(1)
        ->and($userFollows->first()->follower_type)->toBe(User::class);
});

it('scope followableType filters by followable type', function () {
    Follow::create(['follower_id' => 1, 'follower_type' => User::class, 'followable_id' => 2, 'followable_type' => Page::class]);
    Follow::create(['follower_id' => 3, 'follower_type' => User::class, 'followable_id' => 4, 'followable_type' => User::class]);

    $pageFollows = Follow::followableType(Page::class)->get();

    expect($pageFollows)->toHaveCount(1)
        ->and($pageFollows->first()->followable_type)->toBe(Page::class);
});
