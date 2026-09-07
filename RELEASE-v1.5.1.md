## Fix `env-settings:diff` on object and null values

`env-settings:diff` rendered values with its own copy of the formatter used by `env-settings:show`, and that copy was missing two of `show`'s guards. Both are fixed.

### A property holding an object took the command down

A settings class may hold any object — a `DateTimeImmutable`, a value object, anything that is not a nested settings class or an enum. `show` prints a placeholder for it. `diff` fell through to a string cast:

```
Error: Object of class DateTimeImmutable could not be converted to string
```

Not a bad row — the whole command, before printing anything. `diff` now renders it the way `show` always has:

```
| starts_at * | [object: DateTimeImmutable] | [object: DateTimeImmutable] |
```

### `null` and `''` compared as equal

This one is quieter and worse. `diff` compares the *rendered* strings, and an unguarded `null` rendered as `''` — identical to an empty string. So a property that was `null` in one environment and `''` in the other was reported as **no difference**:

```
| label   |            |            |     ← no *, and they are not the same value
```

A command whose only job is finding differences was hiding one. Now:

```
| label * | null       |            |
```

### Why this happened, and what comes next

`formatValue()` is duplicated in `ShowEnvSettingsCommand` and `DiffEnvSettingsCommand`. `show` was fixed at some point; `diff` was not, and nothing forced the two to agree. This release copies the guards across so the two are now identical in behaviour — deliberately, rather than extracting them, so the patch stays a fix. The extraction into a single shared formatter comes with the next feature release.

### Upgrading

No API change and nothing to do. If your settings classes hold only scalars, arrays, enums and nested settings, nothing about your output changes. Two new tests cover the cases above; removing either guard fails them.

**Full changelog:** https://github.com/HPWebdeveloper/laravel-env-settings/compare/v1.5.0...v1.5.1
