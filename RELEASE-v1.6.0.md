## `env-settings:show --all` — every environment side by side

A settings class states every environment in one file. That is the whole argument for having it: a `.env` can only ever describe the machine it is on, and a settings class describes all of them at once.

The commands did not deliver that. `show` printed the current environment, `diff` printed exactly two, and there was no third option — so seeing the whole picture meant reading the source. With four environments, *"where do these differ?"* took six `diff` runs that you then merged in your head.

```bash
php artisan env-settings:show --all
php artisan env-settings:show "App\Settings\AuthSettings" --all
```

```
[ AuthSettings ] — development, staging, production
+---------------+----------------------+--------------------------+------------------+
| Property      | development          | staging                  | production       |
+---------------+----------------------+--------------------------+------------------+
| domain *      | dev.auth.example.com | staging.auth.example.com | auth.example.com |
| timeout *     | 30                   | 20                       | 10               |
| mfa_enabled * | false                | true                     | true             |
| currency      | EUR                  | EUR                      | EUR              |
+---------------+----------------------+--------------------------+------------------+
* = differs somewhere
1 unmarked property is the same everywhere.
```

### The unmarked rows are the interesting part

A property with the same value in every environment does not vary by environment — so it may not need to be a setting at all. A `config()` entry or a constant would do, and the settings class gets smaller.

Nothing else in the package can tell you this. `show` sees one environment, `diff` sees two, and neither can conclude "this never changes." That trailing count is the reason to run the matrix on a class you have not looked at in a while.

### Details

- **Columns are the class's own factories**, called directly, so you see the set of environments the class declares rather than whatever `APP_ENV` happens to be.
- **Values are compared before masking**, so a `#[Sensitive]` property is still marked `*` when it differs between environments without being revealed.
- **With no class argument**, every registered class gets its own matrix.
- Enums, nested settings, arrays and nulls render exactly as they do in `show`.

### Internal: one formatter instead of two

`show` and `diff` each carried their own copy of the value formatter, and they had already drifted apart once — the copy in `diff` was missing two guards, which crashed the command on an object and hid a real difference between `null` and `''` (fixed in v1.5.1).

Both now share a single `FormatsValues` concern. Because v1.5.1 had already brought the two copies to identical behaviour, this release's extraction is a pure refactor: no output changes anywhere.

### Upgrading

Purely additive — one new flag on an existing command. `env-settings:show` without `--all` behaves exactly as before, and there is no change to any other command or to the public API.

**Full changelog:** https://github.com/HPWebdeveloper/laravel-env-settings/compare/v1.5.1...v1.6.0
