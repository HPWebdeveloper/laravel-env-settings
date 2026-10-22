## Fix `show --all` advice, inherited columns and `--shared` numbers

Fixes for `env-settings:show --all` and `env-settings:make --shared`, found by running v1.7.0 against real classes. No API change.

### `show --all` no longer repeats advice you have already taken

v1.7.0 named every property that never varied and suggested a constructor default for it — including properties that already were one. Take a class that has adopted the pattern for `currency` but still repeats `locale` in each factory:

```php
public function __construct(
    public string $provider,
    public string $locale,
    public string $currency = 'EUR',   // written once
) {}

public static function development(): static { return new self(provider: 'ollama', locale: 'de'); }
public static function production(): static  { return new self(provider: 'openai', locale: 'de'); }
```

v1.7.0 printed four columns and told you to fix both:

```
2 properties are the same in every environment: locale, currency
Each can be a constructor default instead, written once — see env-settings:make --shared.
```

v1.7.2:

```
[ AiSettings ] — development, production
+------------+-------------+------------+
| Property   | development | production |
+------------+-------------+------------+
| provider * | ollama      | openai     |
| locale     | de          | de         |
| currency   | EUR         | EUR        |
+------------+-------------+------------+
* = differs somewhere
1 property is repeated with the same value in every environment: locale
Each can be a constructor default instead — written once, in the signature.
Not shown: staging() and testing(), which fall back to development().
```

Only a value still written out in every factory is named now:

- A property holding its declared default — on the constructor parameter or on the property itself — is left out.
- A default that every factory overrides with the same value is still named: the literal is still repeated.
- A nested settings class or an object is left out. It renders as its class name, so the matrix cannot tell whether it repeats.

The pointer at `env-settings:make --shared` is gone: `show --all` only ever looks at existing classes, `--shared` only creates new ones, and it refuses arrays and enums.

### Inherited `staging()` and `testing()` are named instead of shown

Every settings class inherits `staging()` and `testing()`, which return `development()`. `show --all` gave each a column on every class — on most classes, two columns that only repeated development's. Now a class gets a column for each environment it defines, and the line at the bottom names the rest, so the fact that staging runs development's values stays visible without the copies.

`env-settings:diff` offers the same list when it prompts. Naming an inherited environment still works:

```bash
php artisan env-settings:diff "App\Settings\AiSettings" staging production
```

### `--shared` refuses numbers PHP would read differently

`env-settings:make --shared` wrote numbers as typed, and PHP does not always read them back that way:

| `--shared` | v1.7.0 wrote | PHP read it as |
| --- | --- | --- |
| `rate=1e3` (float) | `1e3.0` | parse error |
| `timeout=010` (int) | `010` | `8` — octal |
| `port=08` (int) | `08` | parse error |

A float in exponent form is now written as typed. An int with a leading zero is refused with a message saying why, and so is one past `PHP_INT_MAX`, which PHP would turn into a float.

### Not fixed in this release

`show --all` and `diff` still compare a nested settings class by its class name, so a difference inside a composed class's children goes unreported.

### Upgrading

No API change and nothing to do. Console output changes in three places — the `show --all` footer, the columns for a class that inherits `staging()` or `testing()`, and the environments `diff` offers when it prompts — so only scripts that parse that output are affected.

Development dependencies were bumped too (PHPStan, Larastan, composer-normalize); installs are unaffected.

**Full changelog:** https://github.com/HPWebdeveloper/laravel-env-settings/compare/v1.7.1...v1.7.2
