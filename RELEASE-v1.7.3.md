## Fix nested settings in `show --all` and `diff`, and duplicate registration in `env-settings:make`

Two bugs, both reported from the demo application. No API change.

### A composed class no longer looks like it never changes

`show --all` and `diff` decided whether two values differ by comparing what they print — and a nested settings class prints as its class name. A root class composed of children that differ in every environment looked the same everywhere:

```php
public function __construct(
    public AuthSettings $auth,
    public PaymentSettings $payment,
) {}

public static function development(): static { return new self(AuthSettings::development(), PaymentSettings::development()); }
public static function production(): static  { return new self(AuthSettings::production(), PaymentSettings::production()); }
```

v1.7.2 reported the opposite of the truth:

```
| auth     | [nested: App\Settings\AuthSettings]    | [nested: App\Settings\AuthSettings]    |
| payment  | [nested: App\Settings\PaymentSettings] | [nested: App\Settings\PaymentSettings] |
+----------+----------------------------------------+----------------------------------------+
No property varies by environment — these values may not need a settings class at all.
```

and `diff` printed `No differences found.` — the same kind of hidden difference v1.5.1 fixed for `null` and `''`.

v1.7.3 compares a nested class by what it holds:

```
[ AppSettings ] — development, production
+-----------+----------------------------------------+----------------------------------------+
| Property  | development                            | production                             |
+-----------+----------------------------------------+----------------------------------------+
| auth *    | [nested: App\Settings\AuthSettings]    | [nested: App\Settings\AuthSettings]    |
| payment * | [nested: App\Settings\PaymentSettings] | [nested: App\Settings\PaymentSettings] |
+-----------+----------------------------------------+----------------------------------------+
* = differs somewhere
A nested class marked * differs inside: run env-settings:show --all on it to see where.
Not shown: staging() and testing(), which fall back to development().
```

The cells still name the class rather than spill its values — a nested class may hold `#[Sensitive]` ones — so a line under the table points at it when one is marked. `diff` does the same.

Any other object is compared by its state too, so a `DateTimeImmutable` that differs between environments now gets its `*` as well. Equal values held in separate instances still compare equal; only something PHP cannot serialise, such as a closure, falls back to the class name.

### `env-settings:make` no longer registers a class twice

The command appends a new class to `register` unless it is already listed, but it compared names exactly as written. An entry written short against a `use` import — the style many configs use, the demo application's included — never matched:

```php
use App\Settings\AiSettings;

return [
    'register' => [
        AiSettings::class,
    ],
];
```

v1.7.2 appended it again:

```
  → Registered \App\Settings\AiSettings::class in config/env-settings.php
```

```php
    'register' => [
        AiSettings::class,
        \App\Settings\AiSettings::class,
    ],
```

v1.7.3 resolves the config's imports before comparing — plain, aliased (`use … as …`), several in one statement, and grouped (`use App\Settings\{AiSettings, AuthSettings};`) — and compares names case-insensitively, as PHP does:

```
  → \App\Settings\AiSettings::class is already registered in config/env-settings.php
```

A short name with no matching import is a different class — PHP reads `AiSettings::class` there as the global `\AiSettings` — so the settings class is still registered, as before.

### Upgrading

No API change and nothing to do. `show --all` and `diff` now mark nested settings classes and objects that differ, and print one extra line when a nested class is marked. If an earlier `env-settings:make` already left a duplicate entry in your config, delete it by hand: this release stops new duplicates, it does not remove old ones.

**Full changelog:** https://github.com/HPWebdeveloper/laravel-env-settings/compare/v1.7.2...v1.7.3
