<?php

use UltraTechInnovations\SocialFollow\Models\Block;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Tests\TestModels\User;
use Tests\TestModels\Page;
use function Pest\Laravel\artisan;


beforeEach(function () {
    // Setup table prefix for testing
    config(['social-follow.table_prefix' => 'test_']);

    // Create tables with proper prefixed names
    if (!Schema::hasTable('test_blocks')) {
        Schema::create('test_blocks', function ($table) {
            $table->id();
            $table->unsignedBigInteger('blocker_id');
            $table->string('blocker_type');
            $table->unsignedBigInteger('blockable_id');
            $table->string('blockable_type');
            $table->timestamps();
        });
    }

    if (!Schema::hasTable('test_users')) {
        Schema::create('test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    if (!Schema::hasTable('test_pages')) {
        Schema::create('test_pages', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }
});

afterEach(function () {
    // Clean up tables after tests
    Schema::dropIfExists('test_blocks');
    Schema::dropIfExists('test_users');
    Schema::dropIfExists('test_pages');
});

it('uses correct table name with prefix', function () {
    $block = new Block();

    expect($block->getTable())->toBe('test_blocks');
});

it('has correct fillable attributes', function () {
    $block = new Block();

    expect($block->getFillable())->toBe([
        'blocker_id',
        'blocker_type',
        'blockable_id',
        'blockable_type',
    ]);
});

it('can be created with valid attributes', function () {
    $block = Block::create([
        'blocker_id' => 1,
        'blocker_type' => User::class,
        'blockable_id' => 2,
        'blockable_type' => Page::class,
    ]);

    expect($block)->toBeInstanceOf(Block::class)
        ->and($block->blocker_id)->toBe(1)
        ->and($block->blocker_type)->toBe(User::class)
        ->and($block->blockable_id)->toBe(2)
        ->and($block->blockable_type)->toBe(Page::class);
});

it('has morphTo relationship for blocker', function () {
    $block = new Block();

    expect($block->blocker())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class);
});

it('has morphTo relationship for blockable', function () {
    $block = new Block();

    expect($block->blockable())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class);
});

it('can retrieve blocker model', function () {
    // Create test user
    $user = User::create(['name' => 'Test User']);

    $block = Block::create([
        'blocker_id' => $user->id,
        'blocker_type' => User::class,
        'blockable_id' => 1,
        'blockable_type' => Page::class,
    ]);

    expect($block->blocker)->toBeInstanceOf(User::class)
        ->and($block->blocker->id)->toBe($user->id);
});

it('can retrieve blockable model', function () {
    // Create test page
    $page = Page::create(['name' => 'Test Page']);

    $block = Block::create([
        'blocker_id' => 1,
        'blocker_type' => User::class,
        'blockable_id' => $page->id,
        'blockable_type' => Page::class,
    ]);

    expect($block->blockable)->toBeInstanceOf(Page::class)
        ->and($block->blockable->id)->toBe($page->id);
});

it('requires all fields for creation', function () {
    expect(fn() => Block::create([]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
