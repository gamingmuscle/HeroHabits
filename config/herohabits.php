<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hero Habits Application Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration values for the Hero Habits gamified task tracking system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'default' => env('PAGINATION_DEFAULT', 20),
        'max' => env('PAGINATION_MAX', 100),
        'quest_history' => 50,
        'treasure_history' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    */
    'session' => [
        'parent_timeout' => env('PARENT_SESSION_TIMEOUT', 1800), // 30 minutes
        'child_timeout' => env('CHILD_SESSION_TIMEOUT', 1800), // 30 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Quest Configuration
    |--------------------------------------------------------------------------
    */
    'quests' => [
        'max_gold_reward' => 1000,
        'min_gold_reward' => 1,
        'max_title_length' => 100,
        'max_description_length' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Treasure Configuration
    |--------------------------------------------------------------------------
    */
    'treasures' => [
        'max_gold_cost' => 10000,
        'min_gold_cost' => 1,
        'max_title_length' => 100,
        'max_description_length' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Child Profile Configuration
    |--------------------------------------------------------------------------
    */
    'children' => [
        'min_age' => 1,
        'max_age' => 18,
        'max_name_length' => 50,
        'pin_length' => 4,
        'default_avatar' => 'princess_3tr.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'api_authenticated' => '60,1', // 60 requests per minute
        'api_auth_attempts' => '5,1',   // 5 attempts per minute for login
        'api_child_auth' => '10,1',     // 10 attempts per minute for child login
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'quest_list_ttl' => 300, // 5 minutes
        'treasure_list_ttl' => 300, // 5 minutes
        'dashboard_stats_ttl' => 600, // 10 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Paths
    |--------------------------------------------------------------------------
    */
    'assets' => [
        'avatar_path' => 'Assets/Profile',
        'allowed_avatar_extensions' => ['png', 'jpg', 'jpeg', 'gif', 'webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration Configuration
    |--------------------------------------------------------------------------
    */
    'registration' => [
        'invitation_only' => env('REGISTRATION_INVITATION_ONLY', false),
        'invitation_code_length' => 8,
        'invitation_expiry_days' => 30, // 0 = never expires
    ],
];
