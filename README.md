# Laravel Env Settings

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hpwebdeveloper/laravel-env-settings.svg?style=flat-square)](https://packagist.org/packages/hpwebdeveloper/laravel-env-settings)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/hpwebdeveloper/laravel-env-settings/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/hpwebdeveloper/laravel-env-settings/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/hpwebdeveloper/laravel-env-settings.svg?style=flat-square)](https://packagist.org/packages/hpwebdeveloper/laravel-env-settings)

First read this part "This package is for you if…" from the
readme of this package:
https://github.com/HPWebdeveloper/document-hb-pattern

and add more from there to this readme.

**Environment-aware, type-safe configuration classes for Laravel.**

Move non-secret values out of `.env` and into typed, IDE-friendly PHP classes that resolve automatically based on your current _environment_.

---

## This package is for you if…

- You believe **`.env` is for secrets** — not for URLs, model names, queue priorities, and scheduler intervals that have no business hiding outside version control
- Your team has **3+ developers** and you're tired of "what's the staging value for X?" messages — because the answer is buried in a `.env` file no one can see in a PR
- Your application runs across **multiple environments** (local, staging, production) and your `.env` files are 80+ lines of values that aren't secret at all
- You want **fully typed settings** with IDE autocomplete, so `envSettings(AuthSettings::class)->domain` replaces `config('services.auth0.domain')` and the stringly-typed guessing game ends
- You've been burned by `env()` returning `null` in production because someone forgot that **`env()` doesn't work after `config:cache`**
- You run **different AI models per environment** — a cheap model for development, mid-tier for staging, the best for production — and you want that choice to be explicit, typed, and reviewable in code

```php
// Before: scattered, untyped, invisible to code review
$domain = config('services.auth0.domain');   // typo? runtime surprise
$model = env('OPENAI_TEXT_MODEL');           // string? null? who knows
$queue = env('PAYMENT_QUEUE', 'default');    // what's production's value? check the server

// After: typed, environment-aware, in version control
envSettings(AuthSettings::class)->domain         // string, IDE autocomplete
envSettings(AiSettings::class)->text_model       // defined per environment
envSettings(QueueSettings::class)->payment       // visible in git, reviewable in PRs
```

> **Note**: This package is NOT a database-backed settings manager (use [spatie/laravel-settings](https://github.com/spatie/laravel-settings) for that). It is NOT a feature flag system (use [Laravel Pennant](https://laravel.com/docs/pennant) for that). It is a **typed configuration layer** for non-secret values that differ between environments.

---

## Requirements

- PHP 8.3+
- Laravel 12.x
- [Spatie Laravel Data](https://github.com/spatie/laravel-data) 4.x

## Installation

```bash
composer require hpwebdeveloper/laravel-env-settings
```

Publish the config file:

```bash
php artisan vendor:publish --tag="env-settings-config"
```

This creates `config/env-settings.php`.

---

## Quick Start

### 1. Generate a settings class

```bash
php artisan env-settings:make AuthSettings --properties="domain:string,redirect_url:string,timeout:int,mfa_enabled:bool"
```

This creates `app/Settings/AuthSettings.php`:

```php
<?php

namespace App\Settings;

use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;

class AuthSettings extends EnvironmentSettings
{
    public function __construct(
        public string $domain,
        public string $redirect_url,
        public int $timeout,
        public bool $mfa_enabled,
    ) {}

    public static function development(): static
    {
        return new static(
            domain: 'dev.auth.example.com',
            redirect_url: 'http://localhost:8000/callback',
            timeout: 30,
            mfa_enabled: false,
        );
    }

    public static function production(): static
    {
        return new static(
            domain: 'auth.example.com',
            redirect_url: 'https://app.example.com/callback',
            timeout: 10,
            mfa_enabled: true,
        );
    }
}
```

### 2. Register it

Add your class to `config/env-settings.php`:

```php
'register' => [
    \App\Settings\AuthSettings::class,
],
```

### 3. Use it anywhere

```php
// Via the helper function
$domain = envSettings(AuthSettings::class)->domain;

// Via the container
$auth = app(AuthSettings::class);
$auth->timeout; // 10 in production, 30 in development

// Type-hinted injection
public function __construct(private AuthSettings $auth) {}
```

That's it. The package resolves the correct environment automatically based on `APP_ENV`.

---

## How It Works

Your settings classes extend `EnvironmentSettings` and define static factory methods for each environment:

```php
class PaymentSettings extends EnvironmentSettings
{
    public function __construct(
        public string $mode,
        public string $currency,
        public int $retry_attempts,
    ) {}

    public static function development(): static
    {
        return new static(mode: 'test', currency: 'EUR', retry_attempts: 1);
    }

    public static function production(): static
    {
        return new static(mode: 'live', currency: 'EUR', retry_attempts: 5);
    }
}
```

When the package resolves a settings class, it:

1. Reads `APP_ENV` (e.g. `local`, `staging`, `production`)
2. Maps it via the `environment_map` config (e.g. `local` → `development`)
3. Calls the corresponding static method (e.g. `::development()`)
4. Returns a fully typed instance

### Required methods

- `development()` — **required** (abstract)
- `production()` — **required** (abstract)

### Optional methods

- `staging()` — defaults to `development()` if not defined
- `testing()` — defaults to `development()` if not defined

Override `staging()` or `testing()` only when their values differ from development:

```php
public static function staging(): static
{
    return new static(
        mode: 'test',
        currency: 'EUR',
        retry_attempts: 3, // more than dev, less than prod
    );
}
```

---

## The `envSettings()` Helper

The global helper resolves a settings class from the container:

```php
envSettings(AuthSettings::class)->domain;
envSettings(PaymentSettings::class)->mode;
```

It is template-typed, so your IDE provides full autocomplete on the returned instance.

---

## Registering Settings

Add your classes to the `register` array in `config/env-settings.php`:

```php
'register' => [
    \App\Settings\AuthSettings::class,
    \App\Settings\PaymentSettings::class,
    \App\Settings\AiSettings::class,
],
```

Each class is registered as a **singleton** in the service container, resolved once via `::resolve()` and reused for the lifetime of the request.

You can also register manually in a service provider:

```php
$this->app->singleton(AuthSettings::class, fn () => AuthSettings::resolve());
```

---

## Root Settings with Nested Sub-Settings

For applications with many settings classes, create a root settings object that composes them:

```php
class AppSettings extends EnvironmentSettings
{
    public function __construct(
        public AuthSettings $auth,
        public PaymentSettings $payment,
        public AiSettings $ai,
    ) {}

    public static function development(): static
    {
        return new static(
            auth: AuthSettings::development(),
            payment: PaymentSettings::development(),
            ai: AiSettings::development(),
        );
    }

    public static function production(): static
    {
        return new static(
            auth: AuthSettings::production(),
            payment: PaymentSettings::production(),
            ai: AiSettings::production(),
        );
    }
}
```

Then access nested settings:

```php
envSettings(AppSettings::class)->auth->domain;
envSettings(AppSettings::class)->payment->mode;
envSettings(AppSettings::class)->ai->text_model;
```

---

## Local Development Overrides

Individual developers can override settings locally without modifying committed code.

### 1. Enable overrides

In your `.env`:

```env
ENV_SETTINGS_OVERRIDE=true
```

### 2. Create an override class

Create `app/Settings/Overrides/AuthSettings.php`:

```php
<?php

namespace App\Settings\Overrides;

use App\Settings\AuthSettings as BaseAuthSettings;

class AuthSettings extends BaseAuthSettings
{
    public static function development(): static
    {
        return new static(
            domain: 'my-custom-domain.local',
            redirect_url: 'http://localhost:9000/callback',
            timeout: 60,
            mfa_enabled: false,
        );
    }
}
```

### 3. Add to `.gitignore`

```gitignore
app/Settings/Overrides/
```

The override class is used instead of the base class when resolving. When override is disabled or the file doesn't exist, the base class is used as normal.

Configure the override path and namespace in `config/env-settings.php`:

```php
'override' => env('ENV_SETTINGS_OVERRIDE', false),
'override_path' => app_path('Settings/Overrides'),
'override_namespace' => 'App\\Settings\\Overrides',
```

---

## Environment Map

The `environment_map` config maps your `APP_ENV` values to factory method names:

```php
'environment_map' => [
    'local'      => 'development',
    'dev'        => 'development',
    'develop'    => 'development',
    'staging'    => 'staging',
    'stage'      => 'staging',
    'production' => 'production',
    'prod'       => 'production',
    'testing'    => 'testing',
    'test'       => 'testing',
],
```

If `APP_ENV` doesn't match any key, the `fallback_environment` is used (default: `development`).

---

## Artisan Commands

### `env-settings:make`

Generate a new settings class:

```bash
# Basic
php artisan env-settings:make QueueSettings

# With typed properties
php artisan env-settings:make QueueSettings --properties="connection:string,payment_queue:string,max_workers:int"

# Custom path
php artisan env-settings:make QueueSettings --path=app/Settings/Infrastructure
```

### `env-settings:show`

Display resolved settings for the current environment:

```bash
# Show a specific class
php artisan env-settings:show "App\Settings\AuthSettings"

# Show all registered classes
php artisan env-settings:show
```

Output:

```
[ AuthSettings ] — Environment: production

+-------------+--------+-----------------------------------+
| Property    | Type   | Value                             |
+-------------+--------+-----------------------------------+
| domain      | string | auth.example.com                  |
| redirect_url| string | https://app.example.com/callback  |
| timeout     | int    | 10                                |
| mfa_enabled | bool   | true                              |
+-------------+--------+-----------------------------------+
```

Sensitive properties (containing `key`, `secret`, `password`, `token`) are automatically masked with `********`.

### `env-settings:diff`

Compare settings between two environments:

```bash
php artisan env-settings:diff "App\Settings\AuthSettings" development production
```

Output:

```
[ AuthSettings ] — Comparing development vs production

+---------------+---------------------------+-----------------------------------+
| Property      | development               | production                        |
+---------------+---------------------------+-----------------------------------+
| domain *      | dev.auth.example.com      | auth.example.com                  |
| redirect_url *| http://localhost:8000/cb   | https://app.example.com/callback  |
| timeout *     | 30                        | 10                                |
| mfa_enabled * | false                     | true                              |
+---------------+---------------------------+-----------------------------------+
* = values differ between environments
```

---

## Testing Your Settings

Since settings are registered as singletons, you can easily swap them in tests:

```php
// Bind a specific instance for testing
$this->app->singleton(AuthSettings::class, fn () => new AuthSettings(
    domain: 'test.example.com',
    redirect_url: 'http://test/callback',
    timeout: 5,
    mfa_enabled: false,
));

// Or resolve directly for a specific environment
$settings = AuthSettings::development();
$this->assertEquals('dev.auth.example.com', $settings->domain);
```

---

## Real-World Example: AI/LLM Settings

AI-powered applications are a perfect use case — every environment uses different models, token limits, and providers:

```php
class AiSettings extends EnvironmentSettings
{
    public function __construct(
        public string $provider,
        public string $text_model,
        public string $embeddings_model,
        public int $max_tokens,
        public int $thinking_budget,
    ) {}

    public static function development(): static
    {
        return new static(
            provider: 'ollama',           // free, local
            text_model: 'gpt-4o-mini',
            embeddings_model: 'text-embedding-3-small',
            max_tokens: 2000,
            thinking_budget: 512,
        );
    }

    public static function staging(): static
    {
        return new static(
            provider: 'openai',
            text_model: 'gpt-4o',
            embeddings_model: 'text-embedding-3-small',
            max_tokens: 4000,
            thinking_budget: 2048,
        );
    }

    public static function production(): static
    {
        return new static(
            provider: 'openai',
            text_model: 'gpt-5.2-turbo',  // best quality
            embeddings_model: 'text-embedding-3-large',
            max_tokens: 8000,
            thinking_budget: 4096,
        );
    }
}
```

Use it in your Prism/LLM integration:

```php
use EchoLabs\Prism\Prism;

$ai = envSettings(AiSettings::class);

$response = Prism::text()
    ->using($ai->provider, $ai->text_model)
    ->withMaxTokens($ai->max_tokens)
    ->withPrompt('Summarize this document...')
    ->asText();
```

Every model change, every provider swap, every token limit adjustment — visible in a PR, fully typed, zero `.env` juggling.

---

## FAQ

### How is this different from `spatie/laravel-settings`?

Completely different purpose. Spatie's package stores settings **in the database** for runtime changes (think admin panel toggles). This package stores settings **in code** for environment-specific configuration (think API URLs, model names, queue routing). They complement each other.

### Doesn't this violate the 12-Factor App methodology?

The 12-Factor App says config should be stored in the environment. We agree — for **secrets**. But non-secret configuration (URLs, model names, feature flags) benefits from being in version control, type-safe, and reviewable. This package makes a deliberate distinction: secrets stay in `.env`, everything else lives in typed PHP classes.

### What if I need to change a value without redeploying?

Use `.env` for values that must change without deployment. Use this package for values that should be reviewed before they change. In practice, most non-secret config changes (switching an AI model, changing a queue name) deserve a code review anyway.

### Can I use this without Spatie Laravel Data?

Currently, `spatie/laravel-data` is a required dependency. The base `EnvironmentSettings` class extends `Spatie\LaravelData\Data`, which provides DTOs, casting, and validation capabilities out of the box.

### What happens if `APP_ENV` doesn't match any environment?

The package falls back to the `fallback_environment` config value (default: `development`). You can customize this in `config/env-settings.php`.

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Hamed Panjeh](https://github.com/hpwebdeveloper)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
