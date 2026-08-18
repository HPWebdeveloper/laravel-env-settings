---
name: laravel-env-settings-development
description: Create and use typed, environment-aware settings classes with hpwebdeveloper/laravel-env-settings — generating classes, registering them, reading values, local overrides, and keeping non-secret configuration out of .env.
---

# Laravel Env Settings Development

## When to use this skill

Use this skill when:

- Adding configuration to a Laravel app that differs per environment but is **not secret** (API URLs, model names, timeouts, feature modes, queue names)
- Creating, registering, or reading a settings class from this package
- Deciding whether a value belongs in `.env` or in a settings class
- Setting up per-developer local overrides for settings

## Core Concepts

- **`.env` is for secrets and stock Laravel keys only.** Non-secret values that vary by environment live in typed PHP classes under `app/Settings`, committed to version control and reviewable in PRs.
- Each settings class extends `EnvironmentSettings` and defines one static factory per environment. The package resolves the right one automatically from `APP_ENV`.
- Registered classes are **container singletons** — resolved once per request.
- Everything resolves at runtime via `app()->environment()`, never `env()`, so the package is fully `config:cache` safe.
- Anti-pattern: putting API keys, passwords, or tokens in a settings class. They would be committed to git. Secrets stay in `.env` and are read via `config()`.

## Creating a settings class

```shell
php artisan env-settings:make AuthSettings --properties="domain:string,timeout:int,mfa_enabled:bool"
```

Supported property types: `string`, `int`, `float`, `bool`, `array`. The generated class contains `// TODO: set development value` placeholders — always fill them in immediately.

The command auto-appends the class to the `register` array in `config/env-settings.php` when the config is published. If it warns instead, follow the instruction it prints.

Options:

- `--path=app/Modules/Billing/Settings` — the namespace is derived from the path (`App\Modules\Billing\Settings`)
- `--namespace="Acme\Billing\Settings"` — required when `--path` is outside `app/`, otherwise the class will not autoload

## Class anatomy

```php
namespace App\Settings;

use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;

class AuthSettings extends EnvironmentSettings
{
    public function __construct(
        public string $domain,
        public int $timeout,
        public bool $mfa_enabled,
    ) {}

    // Required: development() and production() are abstract.
    public static function development(): static
    {
        return new static(domain: 'dev.auth.example.com', timeout: 30, mfa_enabled: false);
    }

    public static function production(): static
    {
        return new static(domain: 'auth.example.com', timeout: 10, mfa_enabled: true);
    }

    // Optional: staging() and testing() default to development().
    // Only override them when their values genuinely differ.
}
```

## Registering

A settings class is inert until listed in `config/env-settings.php` (publish with `php artisan vendor:publish --tag=env-settings-config`):

```php
'register' => [
    \App\Settings\AuthSettings::class,
],
```

## Reading settings

```php
// Preferred: the template-typed helper (full IDE autocomplete)
envSettings(AuthSettings::class)->domain;

// Constructor injection (it's a container singleton)
public function __construct(private AuthSettings $auth) {}

// Container
app(AuthSettings::class)->timeout;
```

Anti-pattern: `new AuthSettings(...)` or calling `AuthSettings::production()` directly in application code — that bypasses environment resolution and overrides. Direct factory calls are for tests only.

## Environment resolution

`APP_ENV` → `environment_map` → static method. Defaults: `local`/`dev`/`develop` → `development()`, `staging`/`stage` → `staging()`, `production`/`prod` → `production()`, `testing`/`test` → `testing()`. An unmapped `APP_ENV` falls back to `fallback_environment` (default `development`).

## Local developer overrides

Per-developer values without touching committed code:

1. Set `ENV_SETTINGS_OVERRIDE=true` in `.env`
2. Create `app/Settings/Overrides/AuthSettings.php` extending the base class and overriding the factories you need
3. Add `app/Settings/Overrides/` to `.gitignore`

`override_path` in the config is resolved at runtime: `null` → `app_path('Settings/Overrides')`, a relative value is resolved against `app_path()`, an absolute value is used as-is. Prefer `null` or a relative path — never call `app_path()` inside the config file, since `config:cache` would freeze the build machine's absolute path.

## Composing settings

For many settings classes, compose a root object whose factories call the children's factories (`auth: AuthSettings::development()`), then read `envSettings(AppSettings::class)->auth->domain`.

## Inspecting settings

```shell
php artisan env-settings:show                                # all registered classes
php artisan env-settings:show "App\Settings\AuthSettings"    # one class
php artisan env-settings:diff "App\Settings\AuthSettings" development production
php artisan env-settings:diff                                # prompts for class and environments
```

`env-settings:show` masks properties whose names contain `key`, `secret`, `password`, or `token` — but do not rely on this: those values should not be in a settings class at all.

## Testing

```php
// Pin an exact instance
$this->app->singleton(AuthSettings::class, fn () => new AuthSettings(
    domain: 'test.example.com', timeout: 5, mfa_enabled: false,
));

// Or assert a specific environment's values
$this->assertSame('dev.auth.example.com', AuthSettings::development()->domain);
```
