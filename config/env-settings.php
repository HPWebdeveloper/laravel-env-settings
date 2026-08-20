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

    // Resolved at runtime, never at config-load time, so `config:cache` stays
    // portable across hosts:
    //
    //   null                    → app_path('Settings/Overrides')
    //   'Custom/Overrides'      → app_path('Custom/Overrides')
    //   '/mnt/shared/overrides' → used as-is
    //
    // Prefer a relative path over calling app_path() here — see the README.
    'override_path' => null,

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

    /*
    |--------------------------------------------------------------------------
    | Default Settings Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace used by `env-settings:make` when --namespace is not passed.
    | Explicit configuration avoids fragile path-to-namespace derivation that
    | breaks with non-standard PSR-4 mappings.
    |
    */

    'class_namespace' => 'App\\Settings',

];
