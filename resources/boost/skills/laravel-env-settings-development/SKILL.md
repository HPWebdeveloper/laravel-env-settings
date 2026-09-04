---
name: laravel-env-settings-development
description: Create and use typed, environment-aware settings classes with hpwebdeveloper/laravel-env-settings — generating classes, registering them, reading values, declaring environments with #[Environment], masking console output with #[Sensitive], local overrides, verifying completeness in CI with env-settings:check, and keeping non-secret configuration out of .env.
---

# Laravel Env Settings Development

## When to use this skill

Use this skill when:

- Adding configuration to a Laravel app that differs per environment but is **not secret** (API URLs, model names, timeouts, feature modes, queue names)
- Creating, registering, or reading a settings class from this package
- Deciding whether a value belongs in `.env` or in a settings class
- Setting up per-developer local overrides for settings
- Resolving a non-standard `APP_ENV` (`qa`, `uat`, `demo`) to a factory method
- Hiding a property's value in `env-settings:show` / `env-settings:diff` output
- Adding a CI step that fails when a settings class was never finished for an environment

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

Supported property types: `string`, `int`, `float`, `bool`, `array`. For a property with a fixed set of valid values, type it as a PHP enum instead of `string` — a typo then fails at parse time, and the valid set is documented by the type. The enum defines which values are possible; the settings class factories choose which one each environment uses. Never store per-environment values inside the enum: they would be invisible to `show`, `diff`, masking and local overrides. `toArray()` unwraps enums (backed → value, pure → case name), so JSON output keeps working. The generated class contains `// TODO: set development value` placeholders — always fill them in immediately.

The command auto-appends the class to the `register` array in `config/env-settings.php` when the config is published. If it warns instead, follow the instruction it prints.

Options:

- `--path=app/Modules/Billing/Settings` — the namespace is derived from the path (`App\Modules\Billing\Settings`)
- `--namespace="Acme\Billing\Settings"` — required when `--path` is outside `app/`, otherwise the class will not autoload
- `--sensitive=passphrase,api_key` — marks the named properties `#[Sensitive]` in the generated class; unknown names fail the command

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

Resolution order for the current `APP_ENV`:

1. A factory marked `#[Environment]` for that `APP_ENV`
2. `environment_map`, then the raw `APP_ENV` value tried as a method name
3. `fallback_environment` (default `development`), then `development()`

Default map: `local`/`dev`/`develop` → `development()`, `staging`/`stage` → `staging()`, `production`/`prod` → `production()`, `testing`/`test` → `testing()`.

### Declaring environments on the class

`environment_map` lives in the application's config, so reading a settings class does not tell you which environments reach which method — and the same class can resolve differently in two applications. Mark the factory instead and the answer sits beside the code:

```php
use HpWebDeveloper\LaravelEnvSettings\Attributes\Environment;

#[Environment('production', 'prod')]
#[Environment('demo')]        // repeatable: another environment on the same values
public static function production(): static { ... }

#[Environment('qa', 'uat')]   // the method name need not match the environment
public static function qualityAssurance(): static { ... }
```

`APP_ENV=qa`, `uat` or `demo` now resolve with no config edit. Reach for this when the class ships in a package or is shared across applications, or when `APP_ENV` uses names outside the default map — asking every consumer to edit their `environment_map` is not practical.

Environment names are matched **case-sensitively**, as `APP_ENV` is. Where two methods claim the same environment, the first declared wins. Attributes take precedence over `environment_map`, so a class that states which environments it serves is never silently redirected by a map it cannot see; classes without attributes behave exactly as before.

Attribute mappings are inherited down the class hierarchy, so a local override that redeclares a marked factory keeps its mapping — the override's own values are still the ones used.

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
php artisan env-settings:check --env=production              # fails if anything is unfinished
```

### Verifying settings before they ship

`env-settings:check` reports properties left at their generated placeholder and exits non-zero, so it works as a gate rather than a report.

**Where it belongs: in CI, on the branch that deploys — alongside the test suite, not in a deploy hook.** It is a static check that resolves the classes locally, so it needs no network and no production credentials, and a failure caught in the build is a failure that never reaches a server:

```yaml
- name: Check production settings are complete
  run: php artisan env-settings:check --env=production
```

A value counts as incomplete when it equals its placeholder (`''`, `0`, `0.0`, `false`, `[]`, `null`) **and another environment supplies a real value** — the signature of one factory being filled in while another was forgotten. A value empty in every environment is treated as deliberate and never reported. Any string containing `TODO` is always reported.

When an empty value is intentional, mark the property so the check stays quiet:

```php
use HpWebDeveloper\LaravelEnvSettings\Attributes\AllowEmpty;

public function __construct(
    #[AllowEmpty] public string $path_prefix,
) {}
```

### Masking values

Mark a property `#[Sensitive]` and both `env-settings:show` and `env-settings:diff` print `********` instead of its value:

```php
use HpWebDeveloper\LaravelEnvSettings\Attributes\Sensitive;

public function __construct(
    public string $webhook_url,
    #[Sensitive] public string $passphrase,
) {}
```

A marked property is masked whatever it holds. Unmarked **string** properties whose names contain `key`, `secret`, `password`, or `token` are masked too, so classes written before the attribute existed stay covered — but that fallback guesses in both directions (it hides `monkey_api_url`, and misses `passphrase`, `credentials`, `bearer`), so mark the property when it matters. Empty strings are never masked.

Masking affects display only: `toArray()` still returns real values, and `env-settings:diff` compares real values, so a masked property is still flagged with `*` when it differs between environments.

Anti-pattern: treating `#[Sensitive]` as permission to put a secret in a settings class. It is a safety net for console output, not storage — the value is still committed to git. Secrets stay in `.env`.

## Testing

```php
// Pin an exact instance
$this->app->singleton(AuthSettings::class, fn () => new AuthSettings(
    domain: 'test.example.com', timeout: 5, mfa_enabled: false,
));

// Or assert a specific environment's values
$this->assertSame('dev.auth.example.com', AuthSettings::development()->domain);
```
