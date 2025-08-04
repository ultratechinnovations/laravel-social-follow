<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | This value will be prefixed to all Social Follow-related database tables.
    | Useful if you're sharing a database with other apps or packages.
    |
    */
    'table_prefix' => 'social_',

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    */
    'models' => [
        'follow' => \UltraTechInnovations\SocialFollow\Models\Follow::class,
        'block' => \UltraTechInnovations\SocialFollow\Models\Block::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Approval Required
    |--------------------------------------------------------------------------
    */
    'approval_required' => false,

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'enabled' => false,
        'channels' => ['database', 'mail', 'broadcast'], // Supported: database, mail, broadcast
    ],

    /*
    |--------------------------------------------------------------------------
    | Follow System Configuration
    |--------------------------------------------------------------------------
    */
    'follow' => [
        'cache' => [
            'enabled' => env('FOLLOW_CACHE_ENABLED', true),
            'expiration' => 86400,
            'key_prefix' => 'social_follow_'
        ],
        'rate_limiting' => [
            'enabled' => true,
            'attempts' => 30,
            'decay_minutes' => 1
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Block System Configuration
    |--------------------------------------------------------------------------
    */
    'block' => [
        'cache' => [
            'enabled' => env('BLOCK_CACHE_ENABLED', true),
            'expiration' => 86400,
            'key_prefix' => 'social_block_'
        ],
        'auto_unfollow' => true // Whether to automatically unfollow when blocking
    ]
];
