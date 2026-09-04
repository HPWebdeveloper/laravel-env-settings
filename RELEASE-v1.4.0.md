## `env-settings:check` — verify settings before they ship

`env-settings:make` writes placeholders, and nothing has ever complained when one was never filled in:

```php
domain: '',  // TODO: set production value
timeout: 0,  // TODO: set production value
```

A class whose `production()` was forgotten resolves silently to an empty domain and a zero timeout. The application boots, and the failure surfaces later, somewhere unrelated — the exact bug this package exists to prevent, one layer further in.

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

It exits non-zero when anything is incomplete, so it is a gate rather than a report.

### Where to run it

**In CI, on the branch that deploys — alongside your test suite, not in a deploy hook.** It is a static check: it resolves your classes locally, touches no network and needs no production credentials. A failure caught in the build is one that never reaches a server.

```yaml
- name: Check production settings are complete
  run: php artisan env-settings:check --env=production
```

### What counts as incomplete

The `TODO` markers the generator writes are *comments* — invisible once a class is resolved — so the check cannot look for them. It compares instead.

A value equal to its generated placeholder (`''`, `0`, `0.0`, `false`, `[]`, `null`) is reported **only when another environment supplies a real one**. That is the shape of a class where one factory was filled in and another forgotten.

A value that is empty in *every* environment is treated as deliberate and never reported, so `retry_attempts: 0` everywhere stays quiet. Guessing harder than this would fail builds over correct code, which is the fastest way to get a check deleted from CI.

Any string containing `TODO` is always reported.

### `#[AllowEmpty]`

When an empty value is intentional, say so and the check stays quiet:

```php
use HpWebDeveloper\LaravelEnvSettings\Attributes\AllowEmpty;

public function __construct(
    #[AllowEmpty] public string $path_prefix,
) {}
```

### Upgrading

Purely additive — a new command and a new attribute. Nothing existing changed, and a project that never runs `env-settings:check` behaves exactly as before.

**Full changelog:** https://github.com/HPWebdeveloper/laravel-env-settings/compare/v1.3.1...v1.4.0
