Notice: whenever you create a new child package do these steps:

1- update this list: dispatch-sync-to-childs.yml in skeleton

Attention: you need to generate "PAT" twice.

2- make a "PAT" and update the pat on the skeleton to access all childs again. ( the old one doesn't work on the new child)

3- make a "PAT" to get the sync and set it on each specific child.

List of child packages the skeleton dispatch notice to them for being sync:

1- https://github.com/ORG/private-repo

2- https://github.com/ORG/private-repo

3- https://github.com/ORG/private-repo

4- https://github.com/ORG/private-repo

5- https://github.com/ORG/private-repo

6- https://github.com/ORG/private-repo

7- https://github.com/ORG/private-repo

8- https://github.com/ORG/private-repo

9- https://github.com/ORG/private-repo

10- https://github.com/ORG/private-repo

11- https://github.com/ORG/private-repo

12- https://github.com/ORG/private-repo

13- https://github.com/ORG/private-repo

14- https://github.com/ORG/private-repo

# Laravel Package Skeleton

A fresh Laravel-friendly package scaffold ready for customization.

## Quick Bootstrap Examples

Interactive rename + minimal docs (dry run first):

```bash
php bin/bootstrap --dry-run --minimal --ci=basic --test-framework=phpunit
```

Apply with Pest + full CI + platform PHP pin:

```bash
php bin/bootstrap --minimal --ci=full --test-framework=pest --platform-php=8.3
```

After bootstrap or any changes, validate locally:

```bash
composer validate --strict
composer test
composer stan
composer format:check
```

## Template docs (skeleton only)

The following guides are skeleton-only and live under `docs/_skeleton/`. They won't be included when bootstrapping with `--minimal`:

- Setup (PATs and secrets): [Guide for Dispatch Sync Setup](docs/_skeleton/guide-for-dispatch-sync.md)
- Triggers and behavior (auto/manual, what syncs, PR details): [Triggering Sync](docs/_skeleton/trigger-sync.md)
- “Use this template” setup: [Use this template → enable sync](docs/_skeleton/use-this-template-setup.md)

## Docs map

- Fine-grained PAT creation and scopes: `docs/_skeleton/fine-grained-pat-setup.md`
- Dispatch and sync setup (where to put secrets, which repos need what): `docs/_skeleton/guide-for-dispatch-sync.md`
- How sync is triggered and what gets synchronized (auto/manual, watched paths, PR behavior): `docs/_skeleton/trigger-sync.md`
- Child workflow template to copy into new packages: `docs/_skeleton/sync-from-skeleton.yaml`
- Mirroring guide (keeping children aligned with skeleton): `docs/_skeleton/mirroring-skeleton.md`
- Template usage guide (after “Use this template”): `docs/_skeleton/use-this-template-setup.md`
- Backup full CI workflow (optional full matrix): `docs/_skeleton/backup-full-ci.yaml`

## Installation

1. Require the package via Composer (local path or VCS):

```bash
composer require vendor/package
```

2. Publish config and (later) migrations as needed:

```bash
php artisan vendor:publish --tag=package-config
php artisan vendor:publish --tag=package-migrations
```

## What’s included

- Service provider with auto-discovery
- Config file (`config/package.php`)
- Migrations and views publish paths
- Minimal tests with Orchestra Testbench

## Configuration shape

This skeleton ships with a minimal, generic config that you can extend as your domain grows. The config key and filename are derived from your package slug during bootstrap (e.g., `package` will be renamed to your package name during scaffolding).

Default shape:

```php
return [
  'feature_flags' => [
    // 'example' => true,
  ],

  'defaults' => [
    'timezone' => 'UTC',
  ],
];
```

Extending it:

- Add feature switches under `feature_flags` to gate optional behaviors:
  ```php
  'feature_flags' => [
    'audit_logs' => true,
    'send_notifications' => false,
  ],
  ```
- Add sensible defaults under `defaults` for values that callers can override via published config or env:
  ```php
  'defaults' => [
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'retry_count' => 3,
  ],
  ```

Publishing:

```bash
php artisan vendor:publish --tag=<slug>-config
```

Replace `<slug>` with your package key after running bootstrap.

## Next steps

- Add models, migrations, contracts, and services for your domain
- Implement business logic and features
- Write documentation and examples

## Local Demo (Workbench)

This skeleton includes [Orchestra Workbench](https://github.com/orchestral/workbench) for a throwaway demo application to test your package in a real Laravel runtime without creating a separate repo.

### Spin up the demo app

```bash
composer install
php artisan workbench:install          # One-time scaffold of the demo app under workbench/ directory
php artisan serve                      # Serve the demo (http://127.0.0.1:8000)
```

### Develop with rapid feedback

- Package code lives in `src/` as usual.
- The workbench app loads your service provider automatically.
- Add demo routes/controllers inside `workbench/app` or `workbench/routes/web.php` after install.
- Tweak config by publishing or editing `config/<slug>.php` inside the workbench app.

### Running demo-specific migrations (if you add migrations later)

```bash
php artisan migrate
```

### Rebuild or reset

If you need to rebuild the workbench app:

```bash
rm -rf workbench/
php artisan workbench:install
```

### Troubleshooting

- If classes aren’t autoloading, run `composer dump-autoload`.
- Clear caches if config/views/routes seem stale:
  ```bash
  php artisan optimize:clear
  ```
- Ensure your provider is listed under `extra.laravel.providers` in `composer.json` (bootstrap handles this automatically).

### When to commit workbench

The generated workbench directory is usually excluded from version control for published packages. Keep it locally or add patterns to `.gitignore`. If you create example code you want to share, move those snippets into `docs/` or dedicated example classes instead of committing the entire workbench app.

## License

MIT

## Testing

By default, tests run against an in-memory SQLite database.

To run tests:

```bash
composer install
composer test
```

To use a file-based SQLite database instead (optional), set DB_DATABASE to a path:

```bash
DB_CONNECTION=sqlite DB_DATABASE=storage/testing.sqlite composer test
```

## Test inventory docs (automatic)

This package includes a small generator that scans tests and creates a concise Markdown inventory of test names and assertions.

- Generate docs:

```bash
composer docs:tests
```

- Output file: `docs/tests.md`

You can also run the script directly if preferred:

```bash
php bin/generate-test-inventory --path=tests --output=docs/tests.md
```

## Documentation

- Fresh setup guide: [`docs/_skeleton/fresh-setup.md`](docs/_skeleton/fresh-setup.md)
- Bootstrap usage: [`docs/_skeleton/bootstrap-usage.md`](docs/_skeleton/bootstrap-usage.md)
- Try it (quick steps): [`docs/_skeleton/try-it.md`](docs/_skeleton/try-it.md)
- Using this as a GitHub Template: [`docs/_skeleton/GitHub-Template-Repository.md`](docs/_skeleton/GitHub-Template-Repository.md)
- CI best practices (Spatie-inspired): [`docs/_skeleton/ci-best-practices-spatie.md`](docs/_skeleton/ci-best-practices-spatie.md)

### Fine-Grained PAT Setup

For detailed instructions on generating and configuring a fine-grained Personal Access Token (PAT) for syncing workflows, refer to the [Fine-Grained PAT Setup](docs/fine-grained-pat-setup.md) document.

## Workflow mirroring and CI sync

If you create a new package from this skeleton and want to keep its CI/workflows in sync with updates made here:

- Copy the workflow template at `docs/sync-from-skeleton.yaml` from this repository into your new package at `.github/workflows/sync-from-skeleton.yml`.
- For private repositories, create a repository secret named `SYNC_TOKEN` with a PAT that has access to read this skeleton (see the guide below).
- Run the workflow once manually from the Actions tab. It will open a PR with any changes it syncs.

Read the full guide: [`docs/mirroring-skeleton.md`](docs/mirroring-skeleton.md)

## Pre-commit hooks (Pint, normalize, docs)

This repository includes a versioned Git pre-commit hook that helps keep the codebase clean and CI-friendly:

- Runs Laravel Pint on staged files and auto-fixes issues
- Runs composer-normalize to keep `composer.json` formatted deterministically
- Regenerates the test inventory at `docs/tests.md` when tests change

Hook location: `.githooks/pre-commit`

Activation

- Automatic: running `composer install` or `composer update` will configure Git to use `.githooks` and make the hook executable via the `hooks:enable` Composer script.
- Manual (fallback):

```bash
git config core.hooksPath .githooks
chmod +x .githooks/pre-commit
```

Note on syncing to child packages

- For hooks to work locally, two things must be true: the hook file must be present, and Git must be configured to read from `.githooks` (`core.hooksPath`). The sync workflow now copies `.githooks/` to children, and each child’s `composer.json` includes the `hooks:enable` script so developers automatically pick it up after `composer install`/`update`.
- If a child repository had an older hook, syncing ensures they receive the latest `pre-commit`. If a developer already cloned the repo, the next `composer install`/`update` will re-enable hooks automatically, or they can run `composer run hooks:enable` once.

Verify the hook is active

```bash
composer run hooks:enable  # optional one-time run if needed

git config core.hooksPath  # should print .githooks
ls -l .githooks/pre-commit # should show it as executable (-rwxr-xr-x)
```

Run Pint manually (optional):

```bash
composer format         # fix all files
composer format:dirty   # fix only changed files
composer format:check   # check only, no changes
```

## CI checks and Composer hygiene

This repository’s CI runs two Composer-related checks before installing dependencies:

- composer validate --strict (in `.github/workflows/ci.yml`)
- composer normalize --dry-run (in `.github/workflows/composer-normalize.yml`)

Important:

- If you change `composer.json`, you must also run `composer update` in the `skeleton` directory to keep `composer.lock` in sync. Otherwise, the CI “validate --strict” step will fail before dependency installation.
- To verify formatting locally, run `composer normalize --dry-run`. If changes are suggested, run `composer normalize` or apply the diff and commit.

Quick local checklist when touching `composer.json`:

1. Run `composer update` (in `skeleton/`) to regenerate `composer.lock`.
2. Run `composer validate --strict` to confirm schema and lock alignment.
3. Run `composer normalize --dry-run` to ensure the file is normalized.

## Static analysis with Larastan

This skeleton ships with PHPStan plus Larastan for Laravel-aware analysis. The configuration lives in `phpstan.neon.dist` and includes Larastan’s extension.

- Includes: `vendor/larastan/larastan/extension.neon`
- Defaults: `level: 8`, analyzes `src` and `tests` (tests are included by default to improve framework symbol discovery)
- Composer autoload is bootstrapped for richer symbols: `bootstrapFiles: vendor/autoload.php`
- Noise reducers: excludes `vendor` and `tests/Pest.php`; conservative flags (`treatPhpDocTypesAsCertain: false`, etc.)

Stricter checks (opt-in):

- Array value types: enable `checkMissingIterableValueType: true` once your public APIs adopt array generics.
- Larastan rules: if your installed Larastan version provides `rules.neon`, uncomment the include in `phpstan.neon.dist`:
  - `vendor/larastan/larastan/rules.neon`

Guidelines for contributors:

- Prefer explicit array generics in public APIs (e.g., `array<string,mixed>` or `array<int,string>`).
- For interfaces with array parameters/returns, add PHPDoc `@param`/`@return` generics to help static analysis.
- Return concrete Symfony `Response` types (e.g., use `redirect()->to(...)` for a `RedirectResponse`).
- Keep JSON encoding non-fallible (e.g., `JSON_THROW_ON_ERROR`) when returning strings.

### PHPStan config strategy (dist + local overrides)

- `phpstan.neon.dist` in this skeleton is the single source of truth. It should be synced to all child packages as-is.
- Do NOT edit `phpstan.neon.dist` in child packages. Instead, if a package needs repo-specific tweaks, add a sibling `phpstan.neon` that includes the dist and overrides only what’s needed.
- This keeps the common baseline centralized while allowing packages to opt into local policies (e.g., analyze `src` only, or add a small ignore for a local trait).

Example local override (taken from the ExamplePackage package):

```
includes:
  - phpstan.neon.dist

parameters:
  # Analyze only library code in this package
  paths:
    - src

  # Package-specific temporary ignores
  ignoreErrors:
    - '#Trait ExamplePackage\\\\Concerns\\\\HasTeams is used zero times and is not analysed#'
    - '#Trait ExamplePackage\\\\Concerns\\\\InvitesTeams is used zero times and is not analysed#'
```

Optional composer script (per-package) to mirror the policy above:

```
"stan": "phpstan analyse --no-progress --memory-limit=512M src"
```

Notes:

- If your package doesn’t need local tweaks, you can omit `phpstan.neon` entirely and rely only on the shared `phpstan.neon.dist`.
- Keeping repo-specific ignores out of the dist prevents the skeleton sync from overwriting them.

## Local validation (pre-flight) ✅

Run these commands locally before pushing, to mirror what CI enforces. Execute from the repository root:

```bash
composer validate --strict
composer update --no-interaction --prefer-dist --no-progress
./vendor/bin/phpunit
./vendor/bin/phpstan analyse --no-progress
```

## CI presets: minimal/basic vs full

To conserve GitHub Actions minutes, the current CI workflow (`.github/workflows/ci.yml`) runs a minimal single job (Ubuntu + PHP 8.3 + Laravel 12). This catches most issues quickly and keeps pipelines fast.

This skeleton ships with a minimal CI by default. You can switch via bootstrap presets:

- None: no CI files
- Basic: minimal single-job CI
- Full: full OS/PHP/Laravel/dependency matrix

The full matrix example is stored here:

- Backup file: [`docs/_skeleton/backup-full-ci.yaml`](docs/_skeleton/backup-full-ci.yaml)

How to restore the full matrix:

1. Copy `docs/backup-full-ci.yaml` to your workflow path as `/.github/workflows/ci.yml`, or replace the contents of your existing `ci.yml` with it.
2. Commit and push. The CI will run across Ubuntu and Windows, multiple PHP versions (8.1–8.3), Laravel majors (10–12), and both lowest/highest dependency sets.

You can switch back to the minimal CI later by reapplying the simplified `ci.yml` from this skeleton.
