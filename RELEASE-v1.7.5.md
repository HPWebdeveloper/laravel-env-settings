## Fix PHPStan errors in classes written from the docs and the bundled skill

A follow-up to v1.7.4: the docs and the bundled skill now show the tag that release started generating. Documentation and a docblock only — no code change.

### Examples you copy now pass PHPStan

v1.7.4 made `env-settings:make` write `@phpstan-consistent-constructor`, but the README, the guide and the bundled skill still showed settings classes without it. A class written by hand from them — by you, or by an agent following the skill — failed PHPStan exactly as generated classes did before v1.7.4. Every full class example in the three, extracted and analysed on its own:

| | v1.7.4 | v1.7.5 |
| --- | --- | --- |
| Class examples reporting `new.static` | 7 of 9 | 0 of 9 |

- The README Quick Start and the skill's "Class anatomy" show the docblock `env-settings:make` writes, and say to keep it on classes written by hand.
- The guide explains the tag once, after the "Generate" example. Its enum, composition and AI examples carry the one-line form, which Pint's `laravel` preset and PHPStan both accept:

```php
/** @phpstan-consistent-constructor */
class AiSettings extends EnvironmentSettings
```

### Overrides keep the constructor

The tag is a promise that local overrides keep the constructor — v1.7.4 showed PHPStan reporting an override that breaks it. The guide and the skill now state the rule where they explain overrides: override the factories, never the constructor.

One docblock said the opposite. `EnvironmentSettings::with()` explained why it clones by saying a local override may change the constructor's signature. It now gives the reason without that claim: the clone never depends on the constructor's parameters, whatever the runtime class — a local override included.

### Upgrading

No API change and nothing to do. The README and the guide are current on GitHub already; what this release adds to your project is the bundled skill. If your agent learned the package through Laravel Boost, re-run `php artisan boost:install` after updating so it reads the new version.

**Full changelog:** https://github.com/HPWebdeveloper/laravel-env-settings/compare/v1.7.4...v1.7.5
