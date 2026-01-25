# Fresh setup for a new package based on this template

This repo includes a `bin/bootstrap` script that renames namespaces, the service provider, and the config key/file so you can start a new Laravel package in under 30 seconds.

## 1) Create a new repo from this template

- On GitHub: click "Use this template" → "Create a new repository" (or duplicate the repo).
- Or CLI (GitHub CLI):

```bash
# Fresh repository from template
gh repo create <owner>/<new-repo> --private --template <owner>/ExamplePackage

# Or duplicate current working tree including history
# gh repo create <owner>/<new-repo> --private --source=. --remote=origin --push
```

## 2) Install dependencies

```bash
composer install
```

## 3) Run the bootstrapper

```bash
# Make sure it's executable (first time only)
chmod +x bin/bootstrap

# Run it
bin/bootstrap
```

You will be prompted for:

- Vendor (StudlyCase), e.g. `Acme`
- Package name (kebab-case), e.g. `team-management`
- Config key (slug), e.g. `teampkg`

The script will:

- Update `composer.json`:
  - `name` → `<vendor>/<package>`
  - `autoload.psr-4` → `<Vendor\\Package>\\`
  - `autoload-dev.psr-4` → `<Vendor\\Package\\Tests>\\`
  - `extra.laravel.providers` → `<Vendor\\Package\\PackageServiceProvider>`
- Rename `src/ExamplePackageServiceProvider.php` → `src/<Package>ServiceProvider.php` and update contents
- Rename `config/ExamplePackage.php` → `config/<config>.php` and update contents
- Update tests namespaces and any `config('ExamplePackage')` calls to the new key
- Run `composer dump-autoload`

## 4) Verify

```bash
composer test
composer stan
composer format:check
```

## 5) Optional conveniences

- Pre-commit Pint: already set up in `.githooks/pre-commit`
  - If not active, set in this repo: `git config core.hooksPath .githooks`
- Workbench app (optional): add a `testbench.yaml` if you want a demo Laravel app inside the repo.

## 6) CI

Workflows are already configured:

- Tests: matrix for PHP 8.1–8.3 and Laravel 10/11/12 (lowest/highest deps)
- Static Analysis: PHPStan
- Code Style: Laravel Pint (check-only)
- Release: creates a release on tag push and attaches a ZIP named `<repo>-<tag>.zip`

If you adjust supported PHP/Laravel versions in `composer.json`, you may want to tweak the CI matrix.
