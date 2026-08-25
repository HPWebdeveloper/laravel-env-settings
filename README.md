# Laravel Env Settings

<a href="https://laravel-env-settings.hpweb.dev"><img width="882" height="924" alt="Laravel Env Settings demo — resolved settings for the current environment" src="https://github.com/user-attachments/assets/92dec5a7-40df-4a0f-840c-a813ba0af5f6" /></a>

<a href="https://laravel-env-settings.hpweb.dev"><img width="978" height="632" alt="Laravel Env Settings demo — comparing values across environments" src="https://github.com/user-attachments/assets/06bb850c-ebf1-49fb-87e0-337a7b7bcd71" /></a>

<sub>by [Hamed Panjeh](https://hpweb.dev/about)</sub>

### 🚀 [**See how this package works in practice — try the live demo**](https://laravel-env-settings.hpweb.dev)

A real Laravel application with worked examples: settings classes, per-environment values, local overrides, and the Artisan commands in action. The fastest way to understand the package before installing it.

---

## Contents

- [AI Assistants](#ai-assistants)
- [This package is for you if…](#this-package-is-for-you-if)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
  - [1. Generate a settings class](#1-generate-a-settings-class)
  - [2. Register it](#2-register-it)
  - [3. Use it anywhere](#3-use-it-anywhere)
- [How It Works](#how-it-works)
  - [Environment map](#environment-map)
  - [Where generated classes live](#where-generated-classes-live)
- [Composing Settings into a Root Object](#composing-settings-into-a-root-object)
  - [Quick start: two classes](#quick-start-two-classes)
  - [A wider tree](#a-wider-tree)
  - [Exporting the whole tree](#exporting-the-whole-tree)
- [Local Development Overrides](#local-development-overrides)
  - [Configuring the override location](#configuring-the-override-location)
- [Artisan Commands](#artisan-commands)
  - [`env-settings:make`](#env-settingsmake)
  - [`env-settings:show`](#env-settingsshow)
  - [`env-settings:diff`](#env-settingsdiff)
- [Testing](#testing)
- [Example: AI/LLM Settings](#example-aillm-settings)
- [FAQ](#faq)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Security](#security)
- [Credits](#credits)
- [License](#license)

---

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hpwebdeveloper/laravel-env-settings.svg?style=flat-square)](https://packagist.org/packages/hpwebdeveloper/laravel-env-settings)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/HPWebdeveloper/laravel-env-settings/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/HPWebdeveloper/laravel-env-settings/actions?query=workflow%3ACI+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/hpwebdeveloper/laravel-env-settings.svg?style=flat-square)](https://packagist.org/packages/hpwebdeveloper/laravel-env-settings)

**Type-safe, environment-aware configuration for Laravel.**

Move non-secret values out of `.env` and into typed PHP classes that resolve automatically from `APP_ENV`.

Most of a typical `.env` holds no secrets at all — API URLs, model names, timeouts, queue names, feature modes. Those belong in version control, where they are typed, reviewable in pull requests, and visible to your whole team. This package keeps `.env` for secrets and the stock Laravel keys, and puts everything your application adds on top into `app/Settings`.

```php
// Before: scattered, untyped, invisible to code review
$domain = config('services.auth0.domain');   // typo? runtime surprise
$model  = env('OPENAI_TEXT_MODEL');          // string? null? who knows
$mode   = env('PAYMENT_MODE', 'test');       // what's production's value? check the server

// After: typed, environment-aware, in version control
envSettings(AuthSettings::class)->domain     // string, IDE autocomplete
envSettings(AiSettings::class)->text_model   // defined per environment
envSettings(PaymentSettings::class)->mode    // visible in git, reviewable in PRs
```

## AI Assistants

If your team uses AI coding agents, a published **agent skill** teaches Claude Code, Cursor, Codex and others how to work with this package — generating and registering settings classes, reading them, how overrides and testing work, and the rule that matters most: secrets must never be placed in a settings class.

It is listed in the Laravel skills registry at [skills.laravel.cloud](https://skills.laravel.cloud) and installs either way:

```bash
# Skills CLI — Claude Code, Cursor, and friends
npx skills add HPWebdeveloper/laravel-env-settings-skills

# Or through Laravel Boost
php artisan boost:add-skill HPWebdeveloper/laravel-env-settings-skills
```

Because the skill stands alone, your agent can learn the package *before* it is in your project — including how to install it.

The package also ships the same guidance as a bundled [Laravel Boost](https://laravel.com/docs/boost) skill, so `php artisan boost:install` offers it automatically once the package is installed.

## This package is for you if…

- You believe **`.env` is for secrets** — not for URLs, model names, and timeouts that have no reason to hide outside version control.
- Your `.env` files run to dozens of lines that **aren't secret at all**, and nobody can review a change to them.
- You want **fully typed settings** with IDE autocomplete, so `envSettings(AuthSettings::class)->domain` replaces stringly-typed `config()` lookups.
- You've been burned by `env()` returning `null` in production because **`env()` stops working after `config:cache`**.

> **Note**
> This is **not** a database-backed settings manager (use [spatie/laravel-settings](https://github.com/spatie/laravel-settings)) and **not** a feature flag system (use [Laravel Pennant](https://laravel.com/docs/pennant)). It is a typed configuration layer for non-secret values that differ between environments.

## Requirements

| Laravel | PHP       |
| ------- | --------- |
| 13.x    | 8.3 – 8.5 |
| 12.x    | 8.2 – 8.5 |

Every combination above is covered by the CI test matrix. The only runtime dependency is `illuminate/support`, which every Laravel application already has.

## Installation

```bash
composer require hpwebdeveloper/laravel-env-settings
php artisan vendor:publish --tag="env-settings-config"
```

> [!TIP]
> 📘 **[Follow the step-by-step setup guide on the demo →](https://laravel-env-settings.hpweb.dev/getting-started)**
> The same installation walked through in a real application, with the generated files shown at each step.

## Quick Start

### 1. Generate a settings class

```bash
php artisan env-settings:make AuthSettings \
    --properties="domain:string,redirect_url:string,timeout:int,mfa_enabled:bool"
```

This creates `app/Settings/AuthSettings.php` with the structure in place and `// TODO` placeholders for each value. Fill them in:

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

Supported property types: `string`, `int`, `float`, `bool`, `array`.

### 2. Register it

A settings class stays inert until it is listed in `config/env-settings.php`:

```php
'register' => [
    \App\Settings\AuthSettings::class,
],
```

`env-settings:make` appends this line for you when the config has been published. If it can't — the config isn't published, or it has no `register` array — it tells you exactly what to add, so a class is never left silently unregistered.

Each registered class becomes a **singleton** in the service container, resolved once and reused for the lifetime of the request.

### 3. Use it anywhere

```php
// The helper — template-typed, so your IDE autocompletes the result
$domain = envSettings(AuthSettings::class)->domain;

// Constructor injection
public function __construct(private AuthSettings $auth) {}

// The container
app(AuthSettings::class)->timeout;   // 10 in production, 30 in development
```

That's it. The correct environment is resolved automatically.

> [!TIP]
> ▶️ **[Try it yourself in the browser →](https://laravel-env-settings.hpweb.dev/try-it)**
> Switch environments and watch the resolved values change, without installing anything.

## How It Works

Each settings class defines one static factory per environment. When the package resolves a class, it:

1. Reads `APP_ENV` (e.g. `local`, `staging`, `production`)
2. Maps it through the `environment_map` config (e.g. `local` → `development`)
3. Calls the matching static method (e.g. `::development()`)
4. Returns a fully typed instance

Resolution uses `app()->environment()` and `config()` — never `env()` — so it is fully compatible with `php artisan config:cache`.

> [!TIP]
> 🔎 **[See this resolution happening live on the demo →](https://laravel-env-settings.hpweb.dev)**
> The demo prints the current `APP_ENV`, the method it maps to, and the resulting instance.

**Required methods** — `development()` and `production()` are abstract, so every settings class must define both.

**Optional methods** — `staging()` and `testing()` fall back to `development()`. Override them only when their values genuinely differ:

```php
public static function staging(): static
{
    return new static(
        domain: 'staging.auth.example.com',
        redirect_url: 'https://staging.example.com/callback',
        timeout: 20,
        mfa_enabled: true,
    );
}
```

### Environment map

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

If `APP_ENV` matches no key, `fallback_environment` is used (default: `development`).

### Where generated classes live

`env-settings:make` writes to `app/Settings` under the `App\Settings` namespace. Change the default with:

```php
'class_namespace' => 'App\\Settings',
```

This is the fallback only — an explicit `--namespace`, or a namespace derived from `--path`, takes precedence. See [`env-settings:make`](#env-settingsmake).

## Composing Settings into a Root Object

Once an application has several settings classes, reaching for each one separately gets noisy. A root settings class solves that: its properties are other settings classes, so the whole configuration tree hangs off a single entry point.

### Quick start: two classes

Take the `AuthSettings` from the Quick Start and a `PaymentSettings` beside it:

```php
class PaymentSettings extends EnvironmentSettings
{
    public function __construct(
        public string $mode,
        public string $currency,
    ) {}

    public static function development(): static
    {
        return new static(mode: 'test', currency: 'EUR');
    }

    public static function production(): static
    {
        return new static(mode: 'live', currency: 'EUR');
    }
}
```

Group them under one root:

```php
class AppSettings extends EnvironmentSettings
{
    public function __construct(
        public AuthSettings $auth,
        public PaymentSettings $payment,
    ) {}

    public static function development(): static
    {
        return new static(
            auth: AuthSettings::development(),
            payment: PaymentSettings::development(),
        );
    }

    public static function production(): static
    {
        return new static(
            auth: AuthSettings::production(),
            payment: PaymentSettings::production(),
        );
    }
}
```

Register `AppSettings` and read either branch from one place:

```php
envSettings(AppSettings::class)->auth->domain;    // 'auth.example.com' in production
envSettings(AppSettings::class)->payment->mode;   // 'live' in production
```

That is the entire pattern. The rest of this section covers what it buys you.

### A wider tree

The same shape scales to as many classes as you like:

```php
class AppSettings extends EnvironmentSettings
{
    public function __construct(
        public AiSettings $ai,
        public PaymentSettings $payment,
    ) {}

    public static function development(): static
    {
        return new static(
            ai: AiSettings::development(),
            payment: PaymentSettings::development(),
        );
    }

    public static function production(): static
    {
        return new static(
            ai: AiSettings::production(),
            payment: PaymentSettings::production(),
        );
    }
}
```

**Nothing here is automatic.** The package resolves `AppSettings` for the current environment — from there, each factory on the root calls the matching factory on every sub-setting: `production()` calls `AiSettings::production()`, `development()` calls `AiSettings::development()`. That wiring is ordinary code you write and control. An empty constructor will not populate itself.

Read any value from one place:

```php
envSettings(AppSettings::class)->ai->text_model;
envSettings(AppSettings::class)->payment->mode;
```

Register only the root:

```php
'register' => [
    \App\Settings\AppSettings::class,
],
```

Sub-settings are plain instances built by the factory, so they need no registration of their own. Register one individually only if you also want to inject it directly.

### Exporting the whole tree

`toArray()` expands nested settings recursively, so the composed tree serialises as-is:

```php
return response()->json(envSettings(AppSettings::class)->toArray());
```

```json
{
    "ai": {
        "provider": "openai",
        "text_model": "gpt-4o",
        "embeddings_model": "text-embedding-3-large",
        "max_tokens": 8000,
        "temperature": 0.2
    },
    "payment": {
        "mode": "live",
        "currency": "USD",
        "retry_attempts": 5,
        "webhook_url": "https://app.example.com/webhooks/payments"
    }
}
```

Handy for a debug endpoint, a health-check payload, or handing the resolved configuration to a frontend. Nesting is not limited to one level — a sub-setting can compose children of its own in exactly the same way.

## Local Development Overrides

Individual developers can override settings locally without touching committed code.

**1.** Enable overrides in your `.env`:

```env
ENV_SETTINGS_OVERRIDE=true
```

**2.** Create `app/Settings/Overrides/AuthSettings.php`, extending the base class and overriding only the factories you need:

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

**3.** Add the directory to `.gitignore`:

```gitignore
app/Settings/Overrides/
```

The override class is used instead of the base class when resolving. When overrides are disabled or the file doesn't exist, the base class is used as normal.

### Configuring the override location

```php
'override' => env('ENV_SETTINGS_OVERRIDE', false),
'override_path' => null,
'override_namespace' => 'App\\Settings\\Overrides',
```

`override_path` is resolved at runtime, once the application has booted:

| `override_path`           | Resolves to                      |
| ------------------------- | -------------------------------- |
| `null` (default)          | `app_path('Settings/Overrides')` |
| `'Custom/Overrides'`      | `app_path('Custom/Overrides')`   |
| `'/mnt/shared/overrides'` | `/mnt/shared/overrides`          |

> **Note**
> Prefer a relative path over calling `app_path()` in the config file. `config:cache` evaluates each config file once and writes the result to `bootstrap/cache/config.php`, freezing the absolute path as it was when the cache was built. Wherever the app runs from a different directory than the build — Docker multi-stage builds, CI-built artifacts, per-release deploy directories — that path no longer exists, and override lookup silently falls back to the base class. A relative path carries no build-time location.

## Artisan Commands

### `env-settings:make`

```bash
# Basic
php artisan env-settings:make NotificationSettings

# With typed properties
php artisan env-settings:make NotificationSettings \
    --properties="sms_provider:string,rate_limit_per_minute:int,sandbox_mode:bool"

# Custom path — namespace follows the directory
php artisan env-settings:make NotificationSettings --path=app/Settings/Infrastructure

# Explicit namespace, for directories outside the application root
php artisan env-settings:make NotificationSettings \
    --path=packages/billing/src/Settings --namespace="Acme\\Billing\\Settings"
```

**How the namespace is chosen.** A generated class only autoloads if its namespace matches where the file was written, so the namespace is resolved in this order:

1. **`--namespace`**, when given — used exactly as provided.
2. **Derived from `--path`**, when that directory sits under the application root. The root namespace is read from your application's own PSR-4 mapping, so a renamed app root maps correctly.
3. **`config('env-settings.class_namespace')`** — the project-wide default.

| Command                                                    | Namespace                     |
| ---------------------------------------------------------- | ----------------------------- |
| `env-settings:make FooSettings`                             | `App\Settings`                |
| `--path=app/Settings/Infrastructure`                        | `App\Settings\Infrastructure` |
| `--path=app/Modules/Billing/Settings`                       | `App\Modules\Billing\Settings`|
| `--path=packages/billing/src`                               | `App\Settings` + warning      |
| `--path=packages/billing/src --namespace="Acme\Billing"`    | `Acme\Billing`                |

A path outside the application root has no PSR-4 mapping the command can read, so it falls back to the configured default and warns you. Pass `--namespace` in that case.

### `env-settings:show`

```bash
php artisan env-settings:show                                # all registered classes
php artisan env-settings:show "App\Settings\AuthSettings"    # one class
```

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

Properties whose names contain `key`, `secret`, `password`, or `token` are masked with `********`. This is a safety net, not a feature — secrets belong in `.env`, not in a settings class.

### `env-settings:diff`

```bash
# Fully specified
php artisan env-settings:diff "App\Settings\AuthSettings" development production

# Omit any argument and you'll be prompted for it
php artisan env-settings:diff
```

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

## Testing

Settings are singletons, so they are easy to swap:

```php
// Bind a specific instance
$this->app->singleton(AuthSettings::class, fn () => new AuthSettings(
    domain: 'test.example.com',
    redirect_url: 'http://test.example.com/callback',
    timeout: 5,
    mfa_enabled: false,
));

// Or assert a specific environment's values directly
$this->assertSame('dev.auth.example.com', AuthSettings::development()->domain);
```

## Example: AI/LLM Settings

Every environment tends to use different models, providers, and token limits — a good fit for typed, per-environment settings:

```php
class AiSettings extends EnvironmentSettings
{
    public function __construct(
        public string $provider,
        public string $text_model,
        public int $max_tokens,
    ) {}

    public static function development(): static
    {
        return new static(provider: 'ollama', text_model: 'llama3.1', max_tokens: 2000);
    }

    public static function staging(): static
    {
        return new static(provider: 'openai', text_model: 'gpt-4o-mini', max_tokens: 4000);
    }

    public static function production(): static
    {
        return new static(provider: 'openai', text_model: 'gpt-4o', max_tokens: 8000);
    }
}
```

```php
$ai = envSettings(AiSettings::class);

$response = Prism::text()
    ->using($ai->provider, $ai->text_model)
    ->withMaxTokens($ai->max_tokens)
    ->withPrompt('Summarize this document...')
    ->asText();
```

Every model change and provider swap is visible in a pull request, fully typed, with no `.env` juggling.

## FAQ

### How is this different from `spatie/laravel-settings`?

Different purpose. Spatie's package stores settings **in the database** for runtime changes, such as admin panel toggles. This package stores settings **in code** for environment-specific configuration, such as API URLs and model names. They complement each other.

### Doesn't this violate the 12-Factor App methodology?

12-Factor says config belongs in the environment — and for **secrets**, that holds. But non-secret configuration benefits from being version-controlled, type-safe, and reviewable. This package draws that line deliberately: secrets stay in `.env`, everything else lives in typed PHP.

For context on why the stock Laravel keys stay in `.env`: Laravel's shipped `config/*.php` files translate `.env` keys into `config()` entries during the `LoadConfiguration` bootstrap step, and the core managers (database, cache, queue, mail, session, and so on) read from `config()` at boot. `APP_ENV` and `APP_KEY` are read earlier still, before any service provider runs. This package targets only the configuration your application adds on top.

### What if I need to change a value without redeploying?

Use `.env` for values that must change without a deployment. Use this package for values that should be reviewed before they change. Most non-secret config changes — switching a model, renaming a queue — deserve a code review anyway.

### What happens if `APP_ENV` doesn't match any environment?

The package falls back to the `fallback_environment` config value (default: `development`).

## Changelog

See [Releases](https://github.com/HPWebdeveloper/laravel-env-settings/releases) for recent changes.

## Contributing

Issues and pull requests are welcome on [GitHub](https://github.com/HPWebdeveloper/laravel-env-settings).

## Security

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Hamed Panjeh](https://hpweb.dev/about)

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
