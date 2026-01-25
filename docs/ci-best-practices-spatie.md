# CI best practices inspired by Spatie’s package skeleton

This guide documents valuable CI pieces from the Spatie package skeleton and how we apply or adapt them here. Keep this as a reference when tuning CI or upgrading workflows in the future.

## Valuable items to adopt from Spatie

### 1) Problem matchers (clearer inline annotations)

Add problem matchers so GitHub annotates PHP and PHPUnit errors directly on PRs.

```yaml
- name: Setup problem matchers
  run: |
    echo "::add-matcher::${{ runner.tool_cache }}/php.json"
    echo "::add-matcher::${{ runner.tool_cache }}/phpunit.json"
```

Place this in the test workflow after the PHP setup step.

Why: Better diagnostics with minimal cost. Safe on all platforms.

---

### 2) Short job timeouts

Keep jobs from hanging indefinitely by adding timeouts (tune as needed).

```yaml
jobs:
  test:
    timeout-minutes: 5
  phpstan:
    timeout-minutes: 5
```

Why: Saves CI minutes when a process stalls. Increase to 10–15 if your jobs are naturally slower.

---

### 3) Auto-fix code style (optional)

Spatie provides a workflow that runs Pint and auto-commits fixes. Useful for repositories where you want CI to automatically resolve minor styling.

```yaml
name: Fix PHP code style issues

on:
  push:
    paths:
      - "**.php"

permissions:
  contents: write

jobs:
  php-code-styling:
    runs-on: ubuntu-latest
    timeout-minutes: 5
    steps:
      - name: Checkout code
        uses: actions/checkout@v5
        with:
          ref: ${{ github.head_ref }}

      - name: Fix PHP code style issues
        uses: aglipanci/laravel-pint-action@2.6

      - name: Commit changes
        uses: stefanzweifel/git-auto-commit-action@v6
        with:
          commit_message: Fix styling
```

Why: Reduces friction for contributors. Consider running this on branches (not main) to avoid noise in release history.

---

### 4) Update changelog on release (optional)

Spatie includes an `update-changelog.yml` to keep CHANGELOG.md current during releases.

Why: Great if you maintain a curated changelog file. Optional if you rely on GitHub Releases + Release Drafter only.

---

### 5) Deterministic dependency installs with caching

Use the community installer for install-only jobs (e.g., static analysis). We already apply this:

```yaml
- name: Install composer dependencies
  uses: ramsey/composer-install@v3
```

Why: Faster, reliable installs with smart caching, and simpler workflows.

---

## Items we already have (and should keep)

These provide additional hygiene that Spatie’s skeleton doesn’t ship by default but are valuable here.

1. Composer validation and normalization

   - `composer validate --strict` to catch lock drift and schema issues early.
   - `composer normalize --dry-run` to keep `composer.json` ordering consistent.

2. Docs inventory check

   - Generate `docs/tests.md` and fail CI if it’s out of date. Encourages up-to-date test documentation.

3. Windows coverage in the test matrix

   - We run tests on `ubuntu-latest` and `windows-latest` (Spatie does this too). This catches cross-platform bugs and path issues.

4. PHPStan GitHub annotations

   - We run PHPStan with `--error-format=github`, so PRs get inline annotations for findings.

5. Pint “check-only” workflow
   - We run Pint in `--test` mode to enforce style without modifying source. This complements the optional “auto-fix” workflow above.

---

## Notes on our CI differences vs Spatie

- We keep extra hygiene gates (validate/normalize/docs inventory) that Spatie doesn’t include—these are deliberate and helpful.
- We support wider PHP/Laravel matrices than Spatie at times. Our dev-tool constraints are widened so older PHP/Laravel cells can still resolve compatible versions.
- For the test matrix, we follow Spatie’s approach of requiring the framework/testbench versions for each matrix cell before running `composer update`.

---

## Quick checklist when adjusting CI

- Problem matchers present in test workflows.
- Reasonable `timeout-minutes` set on long-running jobs.
- Static analysis uses `ramsey/composer-install@v3` (install from lock, cache enabled).
- Test matrix uses dynamic `composer require` for framework/testbench per cell, then `composer update`.
- Optional: Pint auto-fix workflow on branches; Release Drafter and/or changelog workflow per your release style.
- Keep: `composer validate --strict` + `composer normalize --dry-run` + docs inventory check.

This guide is intentionally conservative—adopt optional items only if they make your daily development easier.
