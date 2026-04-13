<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Environment Map
    |--------------------------------------------------------------------------
    |
    | Maps your APP_ENV values to the static factory method names on your
    | settings classes. For example, if APP_ENV=local, the package will
    | call ::development() on your settings classes.
    |
    */

    'environment_map' => [
        'local' => 'development',
        'dev' => 'development',
        'develop' => 'development',
        'staging' => 'staging',
        'stage' => 'staging',
        'production' => 'production',
        'prod' => 'production',
        'testing' => 'testing',
        'test' => 'testing',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Environment
    |--------------------------------------------------------------------------
    |
    | If the current APP_ENV doesn't match any key in the environment map
    | and no matching static method exists, this environment is used.
    |
    */

    'fallback_environment' => 'development',

    /*
    |--------------------------------------------------------------------------
    | Override Settings
    |--------------------------------------------------------------------------
    |
    | When enabled, the package looks for override classes in the override_path.
    | This lets individual developers customize settings locally without
    | modifying the committed settings classes. Add override classes to
    | your .gitignore.
    |
    */

    'override' => env('ENV_SETTINGS_OVERRIDE', false),

    'override_path' => app_path('Settings/Overrides'),

    'override_namespace' => 'App\\Settings\\Overrides',

    /*
    |--------------------------------------------------------------------------
    | Auto-Register Settings
    |--------------------------------------------------------------------------
    |
    | List settings classes here to have them automatically registered as
    | singletons in the service container. Each class will be resolved
    | via its ::resolve() method.
    |
    */

    'register' => [
        // \App\Settings\AuthSettings::class,
        // \App\Settings\PaymentSettings::class,
    ],

];
