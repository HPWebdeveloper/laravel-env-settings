## Values that are the same in every environment, written once

`env-settings:show --all` shipped in v1.6.0 counting the properties that never vary. It was reporting a problem it gave you no way to fix:

```php
public static function development(): static { return new static(provider: 'ollama', currency: 'EUR', temperature: 0.2); }
public static function staging(): static     { return new static(provider: 'openai', currency: 'EUR', temperature: 0.2); }
public static function production(): static  { return new static(provider: 'openai', currency: 'EUR', temperature: 0.2); }
```

Four environments and two constant properties is eight repeated literals. Change one and you must change all of them; miss one and the environments drift apart silently.

### The answer is a constructor default, and it already worked

```php
public function __construct(
    public string $provider,             // varies
    public float $temperature = 0.2,     // written once
    public string $currency = 'EUR',     // written once
) {}

public static function development(): static { return new static(provider: 'ollama'); }
public static function production(): static  { return new static(provider: 'openai'); }

// the one environment that genuinely differs just passes it
public static function staging(): static { return new static(provider: 'openai', temperature: 0.9); }
```

Written once, stated in the signature where a reader looks for a property anyway, fully typed. It works with `show`, `--all`, `diff`, `check`, masking, local overrides and `toArray()` — because it is plain PHP, not a package feature.

**This release adds no mechanism for it.** A `shared()` array merged in by the package would mean constructing reflectively instead of `new static(...)`, which costs the constructor's own type checking, named-argument autocomplete, and the ability to read a settings class as ordinary PHP. That readability is the package's biggest asset; it is not worth trading for a few lines. What was missing was not a feature but the knowledge that PHP already does this — so this release documents it, generates it, and points at it.

### `env-settings:make --shared`

```bash
php artisan env-settings:make AiSettings \
    --properties="provider:string,temperature:float,currency:string" \
    --shared="temperature=0.2,currency=EUR"
```

```php
public function __construct(
    public string $provider,
    // Same in every environment — an environment that differs passes its own.
    public float $temperature = 0.2,
    public string $currency = 'EUR',
) {}

public static function development(): static
{
    return new static(
        provider: '', // TODO: set development value
    );
}
```

Takes `name=value` pairs. Each named property carries its value as a constructor default and is left out of the factories entirely. PHP requires parameters with defaults last, so they are moved to the end of the constructor.

Scalars only. An array or enum default cannot be expressed as one comma-separated argument, so those are refused with a message rather than mangled into a class that will not parse — as are unknown property names and values that do not fit the declared type.

### `show --all` now names them

```
* = differs somewhere
1 property is the same in every environment: currency
Each can be a constructor default instead, written once — see env-settings:make --shared.
```

Counting was not actionable. Naming is.

### Upgrading

Purely additive — one new generator flag and one extra line of console output. Nothing existing changed, and no settings class needs updating: the repetition still works, it is just no longer the only option.

**Full changelog:** https://github.com/HPWebdeveloper/laravel-env-settings/compare/v1.6.0...v1.7.0
