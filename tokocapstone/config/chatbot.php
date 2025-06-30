<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rasa Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for Rasa chatbot integration
    |
    */

    'rasa_path' => env('RASA_PATH', base_path('../Rasa-Tokocapstone')),
    'rasa_url' => env('RASA_URL', 'http://localhost:5005'),
    'training_timeout' => env('RASA_TRAINING_TIMEOUT', 600), // 10 minutes - increased for better training
    'virtual_env' => env('RASA_VIRTUAL_ENV', 'rasaenv2'),

    /*
    |--------------------------------------------------------------------------
    | Training Configuration
    |--------------------------------------------------------------------------
    |
    | Additional training settings
    |
    */

    'training_options' => [
        'force' => true,        // Always force training even if no changes
        'debug' => true,        // Enable debug output
        'verbose' => true,      // Enable verbose output
        'fixed_model_name' => null, // Use null for automatic naming
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for automatic backups during updates
    |
    */

    'backup_enabled' => env('RASA_BACKUP_ENABLED', true),
    'backup_retention_days' => env('RASA_BACKUP_RETENTION', 30),
];
