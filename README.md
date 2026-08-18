# Laravel Env Settings

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hpwebdeveloper/laravel-env-settings.svg?style=flat-square)](https://packagist.org/packages/hpwebdeveloper/laravel-env-settings)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/HPWebdeveloper/laravel-env-setting/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/HPWebdeveloper/laravel-env-setting/actions?query=workflow%3ACI+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/hpwebdeveloper/laravel-env-settings.svg?style=flat-square)](https://packagist.org/packages/hpwebdeveloper/laravel-env-settings)

### 50–80% of a typical enterprise .env is not secret.

This package helps you keep `.env` for **secrets** and for the **stock Laravel keys**. `app/Settings/*.php` is for the **configuration your application adds on top of Laravel** — third-party integrations, AI providers, payment gateways, microservice endpoints, and every other non-secret value that varies by environment.

Your `.env` gets smaller and holds only secrets. PHP classes hold the non-secret operational truth of the system.

- [Demo application](https://github.com/HPWebdeveloper/laravel-env-setting-demo)
- [Why not just `.env`?](https://github.com/HPWebdeveloper/document-hb-pattern/blob/main/7-why-not-just-env.md)

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
$mode = env('PAYMENT_MODE', 'test');         // what's production's value? check the server

// After: typed, environment-aware, in version control
envSettings(AuthSettings::class)->domain         // string, IDE autocomplete
envSettings(AiSettings::class)->text_model       // defined per environment
envSettings(PaymentSettings::class)->mode        // visible in git, reviewable in PRs
```

> **Note**: This package is NOT a database-backed settings manager (use [spatie/laravel-settings](https://github.com/spatie/laravel-settings) for that). It is NOT a feature flag system (use [Laravel Pennant](https://laravel.com/docs/pennant) for that). It is a **typed configuration layer** for non-secret values that differ between environments.

---

## Requirements

- PHP 8.2+
- Laravel 12.x / 13.x

| Laravel | PHP       |
| ------- | --------- |
| 13.x    | 8.3 / 8.4 |
| 12.x    | 8.2 – 8.4 |

Laravel 13 requires PHP 8.3 or newer, so that combination is the floor there. Both are covered by the test suite on every commit.

No other runtime dependencies.

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

This creates `app/Settings/AuthSettings.php` with the structure in place and placeholder values for you to fill in:

```php
<?php

declare(strict_types=1);

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
            domain: '', // TODO: set development value
            redirect_url: '', // TODO: set development value
            timeout: 0, // TODO: set development value
            mfa_enabled: false, // TODO: set development value
        );
    }

    public static function production(): static
    {
        return new static(
            domain: '', // TODO: set production value
            redirect_url: '', // TODO: set production value
            timeout: 0, // TODO: set production value
            mfa_enabled: false, // TODO: set production value
        );
    }
}
```

Fill in the values for each environment:

```php
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
```

### 2. Register it

A settings class stays inert until it is listed in `config/env-settings.php`:

```php
'register' => [
    \App\Settings\AuthSettings::class,
],
```

`env-settings:make` appends this line for you when the config has been published. If it can't — the config isn't published yet, or it has no `register` array — it says so and tells you what to add, so a class is never left silently unregistered.

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

### Where generated classes live

`env-settings:make` writes to `app/Settings` under the `App\Settings` namespace by default. Change the default namespace with:

```php
'class_namespace' => 'App\\Settings',
```

This is the fallback only — an explicit `--namespace`, or a namespace derived from `--path`, takes precedence. See [`env-settings:make`](#env-settingsmake) for the full order.

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

### Configuring the override location

```php
'override' => env('ENV_SETTINGS_OVERRIDE', false),
'override_path' => null,
'override_namespace' => 'App\\Settings\\Overrides',
```

`override_path` is resolved at runtime, once the application has booted. A relative path is resolved against `app_path()`; an absolute path is used as-is:

| `override_path`          | Resolves to                        |
| ------------------------ | ---------------------------------- |
| `null` (default)         | `app_path('Settings/Overrides')`   |
| `'Custom/Overrides'`     | `app_path('Custom/Overrides')`     |
| `'/mnt/shared/overrides'`| `/mnt/shared/overrides`            |

> **Note**: Prefer a relative path over calling `app_path()` in the config file. `php artisan config:cache` evaluates every config file once and writes the result to `bootstrap/cache/config.php`, so `app_path()` freezes the application's absolute path **as it was when the cache was built**. Anywhere the app runs from a different directory than the build — Docker multi-stage builds, CI-built deployment artifacts, Forge/Envoyer releases in per-deploy directories — that cached path no longer exists. Override lookup then silently finds nothing and falls back to the base class, with no error to point at the cause. A relative path carries no build-time location, so it stays correct on every host.

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
php artisan env-settings:make NotificationSettings

# With typed properties
php artisan env-settings:make NotificationSettings --properties="sms_provider:string,default_channel:string,rate_limit_per_minute:int,sandbox_mode:bool"

# Custom path — namespace follows the directory: App\Settings\Infrastructure
php artisan env-settings:make NotificationSettings --path=app/Settings/Infrastructure

# Explicit namespace, for directories outside the application root
php artisan env-settings:make NotificationSettings \
    --path=packages/billing/src/Settings --namespace="Acme\\Billing\\Settings"
```

#### How the namespace is chosen

A generated class only autoloads if its namespace matches where the file was written, so the namespace is resolved in this order:

1. **`--namespace`**, when given — used exactly as provided.
2. **Derived from `--path`**, when that directory sits under the application root. The root namespace is read from your application's own PSR-4 mapping, so a renamed app root maps correctly.
3. **`config('env-settings.class_namespace')`** — the project-wide default, `App\Settings`.

| Command | Namespace |
| ------- | --------- |
| `env-settings:make FooSettings` | `App\Settings` |
| `--path=app/Settings/Infrastructure` | `App\Settings\Infrastructure` |
| `--path=app/Modules/Billing/Settings` | `App\Modules\Billing\Settings` |
| `--path=packages/billing/src` | `App\Settings` + warning |
| `--path=packages/billing/src --namespace="Acme\Billing"` | `Acme\Billing` |

> **Note**: A path outside the application root has no PSR-4 mapping this command can read, so it falls back to the configured default and warns you. Pass `--namespace` in that case — otherwise the file is written with a namespace that will not autoload.

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
+--------------+--------+----------------------------------+
| Property     | Type   | Value                            |
+--------------+--------+----------------------------------+
| domain       | string | auth.example.com                 |
| redirect_url | string | https://app.example.com/callback |
| timeout      | int    | 10                               |
| mfa_enabled  | bool   | true                             |
+--------------+--------+----------------------------------+
```

Sensitive properties (containing `key`, `secret`, `password`, `token`) are automatically masked with `********`.

### `env-settings:diff`

Compare settings between two environments:

```bash
# Fully specified
php artisan env-settings:diff "App\Settings\AuthSettings" development production

# Omit any argument and you'll be prompted for it
php artisan env-settings:diff
```

Output:

```
[ AuthSettings ] — Comparing development vs production
+----------------+--------------------------------+----------------------------------+
| Property       | development                    | production                       |
+----------------+--------------------------------+----------------------------------+
| domain *       | dev.auth.example.com           | auth.example.com                 |
| redirect_url * | http://localhost:8000/callback | https://app.example.com/callback |
| timeout *      | 30                             | 10                               |
| mfa_enabled *  | false                          | true                             |
+----------------+--------------------------------+----------------------------------+
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
            provider: 'ollama',                        // free, local
            text_model: 'llama3.1',
            embeddings_model: 'nomic-embed-text',
            max_tokens: 2000,
            thinking_budget: 512,
        );
    }

    public static function staging(): static
    {
        return new static(
            provider: 'openai',                        // cheap, hosted
            text_model: 'gpt-4o-mini',
            embeddings_model: 'text-embedding-3-small',
            max_tokens: 4000,
            thinking_budget: 2048,
        );
    }

    public static function production(): static
    {
        return new static(
            provider: 'openai',                        // best quality
            text_model: 'gpt-4o',
            embeddings_model: 'text-embedding-3-large',
            max_tokens: 8000,
            thinking_budget: 4096,
        );
    }
}
```

Use it with **Prism** or **Laravel AI SDK**:

```php
// Using Prism (echolabs/prism)
use EchoLabs\Prism\Prism;

$ai = envSettings(AiSettings::class);

$response = Prism::text()
    ->using($ai->provider, $ai->text_model)
    ->withMaxTokens($ai->max_tokens)
    ->withPrompt('Summarize this document...')
    ->asText();
```

```php
// Using Laravel AI SDK (laravel/ai)
use Laravel\Ai\Facades\Ai;

$ai = envSettings(AiSettings::class);

$response = Ai::text()
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

> **How Laravel reads `.env`:** Laravel's shipped `config/*.php` files translate `.env` keys into `config()` entries during the `LoadConfiguration` bootstrap step. Laravel's core managers (DB, cache, queue, mail, log, session, broadcast, filesystem, hasher) then read from `config()` at boot. Two keys — `APP_ENV` and `APP_KEY` — are read even earlier, before any service provider runs. That’s why the stock Laravel keys stay in `.env`, and this package targets only the configuration your application adds on top.

### What if I need to change a value without redeploying?

Use `.env` for values that must change without deployment. Use this package for values that should be reviewed before they change. In practice, most non-secret config changes (switching an AI model, changing a queue name) deserve a code review anyway.

### Does this package have any third-party runtime dependencies?

No. The only runtime requirement beyond PHP 8.2 is `illuminate/support`, which you already have in any Laravel application. `EnvironmentSettings` is a plain abstract PHP class that uses native constructor property promotion and reflection — nothing more.

### What happens if `APP_ENV` doesn't match any environment?

The package falls back to the `fallback_environment` config value (default: `development`). You can customize this in `config/env-settings.php`.

---

## Changelog

See [Releases](https://github.com/HPWebdeveloper/laravel-env-setting/releases) for what has changed recently.

## Contributing

Issues and pull requests are welcome on [GitHub](https://github.com/HPWebdeveloper/laravel-env-setting).

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Hamed Panjeh](https://github.com/hpwebdeveloper)

## License

The MIT License (MIT).
