# Laravel Env Settings

**A typed configuration layer for non-secret values that differ between environments.**

<a href="https://laravel-env-settings.hpweb.dev"><img width="882" height="924" alt="Laravel Env Settings demo — resolved settings for the current environment" src="https://github.com/user-attachments/assets/92dec5a7-40df-4a0f-840c-a813ba0af5f6" /></a>

<a href="https://laravel-env-settings.hpweb.dev"><img width="978" height="632" alt="Laravel Env Settings demo — comparing values across environments" src="https://github.com/user-attachments/assets/06bb850c-ebf1-49fb-87e0-337a7b7bcd71" /></a>

<sub>by [Hamed Panjeh](https://hpweb.dev/about)</sub>

### 🚀 [**See how this package works in practice — try the live demo**](https://laravel-env-settings.hpweb.dev)

A real Laravel application with worked examples: settings classes, per-environment values, local overrides, and the Artisan commands in action. The fastest way to understand the package before installing it.

### 💡 [**Why this exists — the problem it solves**](https://hpweb.dev/learning/laravel/laravel-env-settings#1-the-problem)

The reasoning behind the package: what goes wrong when non-secret configuration lives in `.env`, and why `env()` stops working once you cache your config.

---

## Contents

- [This package is for you if…](#this-package-is-for-you-if)
- [AI Assistants](#ai-assistants)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
  - [1. Generate](#1-generate)
  - [2. Register](#2-register)
  - [3. Use it anywhere](#3-use-it-anywhere)
- [How resolution works](#how-resolution-works)
- [Features](#features)
  - [Fixed-value properties (enums)](#fixed-value-properties-enums)
  - [Declaring environments on the class](#declaring-environments-on-the-class)
  - [Masking sensitive output](#masking-sensitive-output)
  - [Local developer overrides](#local-developer-overrides)
  - [Composing settings into a root object](#composing-settings-into-a-root-object)
- [Artisan Commands](#artisan-commands)
  - [`env-settings:make`](#env-settingsmake)
  - [`env-settings:check`](#env-settingscheck)
  - [`env-settings:show`](#env-settingsshow)
  - [`env-settings:diff`](#env-settingsdiff)
- [Full documentation](#full-documentation)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Security](#security)
- [Credits](#credits)
- [License](#license)

---

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hpwebdeveloper/laravel-env-settings.svg?style=flat-square)](https://packagist.org/packages/hpwebdeveloper/laravel-env-settings)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/HPWebdeveloper/laravel-env-settings/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/HPWebdeveloper/laravel-env-settings/actions?query=workflow%3ACI+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/hpwebdeveloper/laravel-env-settings.svg?style=flat-square)](https://packagist.org/packages/hpwebdeveloper/laravel-env-settings)

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

## This package is for you if…

- You believe **`.env` is for secrets** — not for URLs, model names, and timeouts that have no reason to hide outside version control.
- Your `.env` files run to dozens of lines that **aren't secret at all**, and nobody can review a change to them.
- You want **fully typed settings** with IDE autocomplete, so `envSettings(AuthSettings::class)->domain` replaces stringly-typed `config()` lookups.
- You've been burned by `env()` returning `null` in production because **`env()` stops working after `config:cache`**.
- You want a build to **fail before deploying a half-finished settings class** — `env-settings:check` exits non-zero when a factory was left at its generated placeholders.
- A value has a **fixed set of options** (`live` / `sandbox`), and you want a typo to be a parse error instead of a 3 a.m. incident — type the property as an enum.
- Your `APP_ENV` is `qa`, `uat` or `demo`, and you want the class itself to **declare which environments it serves** with `#[Environment]`, without every consumer editing their config.
- Each developer needs **their own local values** — a different domain, a longer timeout — without touching committed code or their teammates' setup.
- You want to **see what production actually uses** without SSH-ing anywhere: `env-settings:show` prints the resolved values, `env-settings:diff` compares two environments side by side.
- You have **many settings classes** and want them composed into one root object, readable as `envSettings(AppSettings::class)->payment->mode` and exportable with `toArray()`.
- You paste console output into tickets and chat, and want a property **masked in that output** with `#[Sensitive]`.
- You run **AI/LLM workloads** where providers, models and token limits differ per environment and change often — every change reviewable in a pull request.

> **Note**
> This is **not** a database-backed settings manager (use [spatie/laravel-settings](https://github.com/spatie/laravel-settings)) and **not** a feature flag system (use [Laravel Pennant](https://laravel.com/docs/pennant)). It is a typed configuration layer for non-secret values that differ between environments.

## AI Assistants

A published **agent skill** teaches Claude Code, Cursor, Codex and others how to work with this package — and the rule that matters most: secrets never go in a settings class. It is listed at [skills.laravel.cloud](https://skills.laravel.cloud):

```bash
npx skills add HPWebdeveloper/laravel-env-settings-skills        # Skills CLI
php artisan boost:add-skill HPWebdeveloper/laravel-env-settings-skills   # Laravel Boost
```

The skill stands alone, so your agent can learn the package *before* it is installed. The same guidance also ships bundled, so `php artisan boost:install` offers it once the package is in your project.

## Requirements

| Laravel | PHP       |
| ------- | --------- |
| 13.x    | 8.3 – 8.5 |
| 12.x    | 8.2 – 8.5 |

Every combination above is covered by the CI test matrix. The only runtime dependency is `illuminate/support`.

## Installation

```bash
composer require hpwebdeveloper/laravel-env-settings
php artisan vendor:publish --tag="env-settings-config"
```

> [!TIP]
> 📘 **[Follow the step-by-step setup guide on the demo →](https://laravel-env-settings.hpweb.dev/getting-started)**
> The same installation walked through in a real application, with the generated files shown at each step.

## Quick Start

### 1. Generate

```bash
php artisan env-settings:make AuthSettings \
    --properties="domain:string,timeout:int,mfa_enabled:bool"
```

That writes `app/Settings/AuthSettings.php` with the structure in place and `// TODO` placeholders. Fill them in:

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

    public static function development(): static
    {
        return new static(domain: 'dev.auth.example.com', timeout: 30, mfa_enabled: false);
    }

    public static function production(): static
    {
        return new static(domain: 'auth.example.com', timeout: 10, mfa_enabled: true);
    }
}
```

`development()` and `production()` are required. `staging()` and `testing()` fall back to `development()` — override them only when the values genuinely differ.

Property types: `string`, `int`, `float`, `bool`, `array`, or [any enum](#fixed-value-properties-enums).

### 2. Register

A settings class stays inert until it is listed in `config/env-settings.php`:

```php
'register' => [
    \App\Settings\AuthSettings::class,
],
```

`env-settings:make` appends this line for you when the config has been published; if it can't, it prints exactly what to add. Each registered class becomes a **container singleton**, resolved once per request.

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

## How resolution works

For the current `APP_ENV`, the package picks a factory in this order:

1. A method marked **`#[Environment]`** for that `APP_ENV`
2. The **`environment_map`** config (`local` → `development`, `prod` → `production`, …), then the raw `APP_ENV` value as a method name
3. **`fallback_environment`** (default: `development`)

It reads `app()->environment()` and `config()` — never `env()` — so it is fully compatible with `php artisan config:cache`.

> [!TIP]
> 🔎 **[See this resolution happening live on the demo →](https://laravel-env-settings.hpweb.dev)**
> The demo prints the current `APP_ENV`, the method it maps to, and the resulting instance.

## Features

Each of these is covered in depth in the **[full guide](GUIDE.md)**.

### Fixed-value properties (enums)

When a property only ever holds one of a few values, type it as an enum. A typo becomes a parse error, and the valid set is documented by the type:

```php
enum PaymentMode: string
{
    case Live = 'live';
    case Sandbox = 'sandbox';
}

public function __construct(
    public PaymentMode $mode,
) {}

// development(): mode: PaymentMode::Sandbox
// production():  mode: PaymentMode::Live
```

Reading it gives you the case itself, so calling code branches exhaustively instead of comparing strings:

```php
$client = match (envSettings(PaymentSettings::class)->mode) {
    PaymentMode::Live => $gateway->live(),
    PaymentMode::Sandbox => $gateway->sandbox(),
};
```

The enum defines which values are **possible**; the factories choose which one each environment **uses**. `toArray()` unwraps enums (backed → value, pure → case name), so JSON output keeps working. → [details](GUIDE.md#fixed-value-properties)

### Declaring environments on the class

`environment_map` lives in the application's config, so the same class can resolve differently in two applications. Mark the factory instead and the answer sits beside the code:

```php
use HpWebDeveloper\LaravelEnvSettings\Attributes\Environment;

#[Environment('production', 'prod')]
#[Environment('demo')]        // a second environment sharing production values
public static function production(): static { ... }

#[Environment('qa', 'uat')]   // the method name need not match the environment
public static function qualityAssurance(): static { ... }
```

`APP_ENV=demo`, `qa` or `uat` now resolve with no config edit. → [details](GUIDE.md#declaring-environments-on-the-class)

### Masking sensitive output

Mark a property `#[Sensitive]` and both `env-settings:show` and `env-settings:diff` print `********` instead of its value:

```php
use HpWebDeveloper\LaravelEnvSettings\Attributes\Sensitive;

public function __construct(
    #[Sensitive] public string $passphrase,
) {}
```

Masking affects display only — `toArray()` still returns real values, and `diff` still flags the property with `*` when it differs. This is a safety net, not permission to store secrets here. → [details](GUIDE.md#masking-values)

### Local developer overrides

Individual developers can override values without touching committed code: set `ENV_SETTINGS_OVERRIDE=true`, add `app/Settings/Overrides/AuthSettings.php` extending the base class, and gitignore the directory. When overrides are off or the file is missing, the base class is used as normal. → [details](GUIDE.md#local-development-overrides)

### Composing settings into a root object

A root class whose properties are other settings classes gives the whole configuration tree one entry point:

```php
envSettings(AppSettings::class)->auth->domain;
envSettings(AppSettings::class)->payment->mode;
```

Register only the root. `toArray()` expands nested settings recursively, which makes a debug endpoint or health-check payload a one-liner. → [details](GUIDE.md#composing-settings-into-a-root-object)

## Artisan Commands

### `env-settings:make`

```bash
php artisan env-settings:make NotificationSettings \
    --properties="sms_provider:string,rate_limit_per_minute:int,sandbox_mode:bool"

# Custom path — the namespace follows the directory
php artisan env-settings:make NotificationSettings --path=app/Settings/Infrastructure

# Explicit namespace, for directories outside the application root
php artisan env-settings:make NotificationSettings \
    --path=packages/billing/src/Settings --namespace="Acme\\Billing\\Settings"

# Mark properties #[Sensitive] as they are generated
php artisan env-settings:make VaultSettings \
    --properties="endpoint:string,passphrase:string" --sensitive=passphrase
```

A generated class only autoloads if its namespace matches where the file was written, so the namespace is resolved in this order: `--namespace` if given, otherwise derived from `--path` when that directory sits under the application root, otherwise `config('env-settings.class_namespace')`. A path outside the application root has no PSR-4 mapping to read, so it falls back to the default and warns — pass `--namespace` in that case. → [full table](GUIDE.md#env-settingsmake)

### `env-settings:check`

Reports settings left at their generated placeholder, so a factory nobody finished cannot reach production:

```bash
php artisan env-settings:check --env=production   # a specific environment
php artisan env-settings:check                    # the current APP_ENV
php artisan env-settings:check "App\Settings\AuthSettings"
```

```
✗ App\Settings\AuthSettings
    domain                   empty string, but set in development()
    timeout                  0, but set in development()
    webhook_url              still contains "TODO"

1 of 3 classes incomplete for [production]: 3 values to fill in.
```

**Run it in CI, on the branch that deploys.** It exits non-zero when anything is incomplete, which makes it a gate rather than a report:

```yaml
- name: Check production settings are complete
  run: php artisan env-settings:check --env=production
```

It is a static check — it resolves your classes, touches no network and needs no production credentials — so it belongs in the build, alongside your tests, not in a deploy hook where a failure is already too late.

**What counts as incomplete.** A value equal to its generated placeholder (`''`, `0`, `0.0`, `false`, `[]`, `null`) **when another environment supplies a real one** — the shape of a class where one factory was filled in and another forgotten. A value that is empty in *every* environment is deliberate and never reported. Any string containing `TODO` is always reported.

Mark a property `#[AllowEmpty]` when its empty value is intentional:

```php
use HpWebDeveloper\LaravelEnvSettings\Attributes\AllowEmpty;

public function __construct(
    #[AllowEmpty] public string $path_prefix,
) {}
```

### `env-settings:show`

```bash
php artisan env-settings:show                                # all registered classes
php artisan env-settings:show "App\Settings\AuthSettings"    # one class
```

```
[ AuthSettings ] — Environment: production
+-------------+--------+------------------+
| Property    | Type   | Value            |
+-------------+--------+------------------+
| domain      | string | auth.example.com |
| timeout     | int    | 10               |
| mfa_enabled | bool   | true             |
+-------------+--------+------------------+
```

### `env-settings:diff`

```bash
php artisan env-settings:diff "App\Settings\AuthSettings" development production

# Omit any argument and you'll be prompted for it
php artisan env-settings:diff
```

```
[ AuthSettings ] — Comparing development vs production
+---------------+----------------------+------------------+
| Property      | development          | production       |
+---------------+----------------------+------------------+
| domain *      | dev.auth.example.com | auth.example.com |
| timeout *     | 30                   | 10               |
| mfa_enabled * | false                | true             |
+---------------+----------------------+------------------+
* = values differ between environments
```

## Full documentation

**[📖 GUIDE.md](GUIDE.md)** — the complete reference, including:

- [Testing settings classes](GUIDE.md#testing)
- [Composing a wider tree and exporting it](GUIDE.md#composing-settings-into-a-root-object)
- [Configuring the override location](GUIDE.md#configuring-the-override-location) (and why `config:cache` makes relative paths matter)
- [The full `environment_map`](GUIDE.md#environment-map) and where generated classes live
- [A worked AI/LLM example](GUIDE.md#example-aillm-settings)
- [FAQ](GUIDE.md#faq) — how this differs from `spatie/laravel-settings`, and whether it violates 12-Factor

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
