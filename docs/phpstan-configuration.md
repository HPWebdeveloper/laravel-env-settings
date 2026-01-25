# PHPStan Configuration Pattern

## Overview

This repository uses a two-file PHPStan configuration pattern to allow package-specific rules while maintaining synchronization with the skeleton template.

## File Structure

- **`phpstan.neon.dist`** - Base configuration synchronized from skeleton
- **`phpstan-local.neon`** - Package-specific rules (NOT overwritten by sync)
- **`phpstan-baseline.neon`** - Generated baseline (if exists)

## How It Works

### Base Configuration (phpstan.neon.dist)

This file is synchronized from the skeleton package and contains:

- Standard level 8 analysis
- Common paths (src, tests)
- Shared excludePaths
- Basic ignoreErrors for all packages

**⚠️ Do NOT add package-specific rules here** - they will be overwritten during skeleton sync.

### Local Configuration (phpstan-local.neon)

This file contains package-specific PHPStan rules that won't be overwritten:

```neon
# Example phpstan-local.neon
parameters:
  ignoreErrors:
    # Trait provided for package users, not used internally
    - identifier: trait.unused
      path: src/Traits/SomeExportedTrait.php

    # Anonymous class in macro needs type hints
    -
      message: '#Property .* has no type specified#'
      path: src/ServiceProvider.php
```

### Usage

1. The base `phpstan.neon.dist` includes `phpstan-local.neon` automatically
2. Create `phpstan-local.neon` for package-specific rules
3. Run PHPStan normally: `./vendor/bin/phpstan analyse`

## Examples

### ExamplePackage

```neon
# phpstan-local.neon
parameters:
  ignoreErrors:
    # InteractsWithPipeline trait is used in tests but not in src
    -
      message: '#Trait .* is used zero times and is not analysed#'
      path: src/Testing/InteractsWithPipeline.php
```

### ExamplePackage

```neon
# phpstan-local.neon
parameters:
  ignoreErrors:
    # Traits provided for package users, not used internally
    - identifier: trait.unused
      paths:
        - src/Traits/HasGuardContext.php
        - src/Traits/InteractsWithGuards.php
        - src/Traits/RedirectsToGuardHome.php
```

## Migration Guide

If you have existing package-specific rules in `phpstan.neon.dist`:

1. Create `phpstan-local.neon`
2. Move package-specific `ignoreErrors` to `phpstan-local.neon`
3. Ensure `phpstan.neon.dist` includes `phpstan-local.neon` (already done if using latest skeleton)
4. Commit `phpstan-local.neon` to version control
5. Next skeleton sync will only update the base config

## Benefits

✅ **Sync-safe** - Skeleton updates won't overwrite your package-specific rules
✅ **Centralized** - Common rules stay in sync across all packages
✅ **Clear separation** - Easy to see what's package-specific vs. shared
✅ **Version controlled** - Local rules are committed and tracked

## Testing

Verify your configuration:

```bash
# Check src directory only (recommended for CI)
./vendor/bin/phpstan analyse --no-progress src

# Check everything including tests
./vendor/bin/phpstan analyse --no-progress

# Generate baseline if needed
./vendor/bin/phpstan analyse --generate-baseline
```

## Troubleshooting

### "File not found: phpstan-local.neon"

Create an empty file or copy from `phpstan-local.neon.example`:

```bash
cp phpstan-local.neon.example phpstan-local.neon
```

### Rules not applying

Check the includes section in `phpstan.neon.dist`:

```neon
includes:
  - vendor/larastan/larastan/extension.neon
  - phpstan-local.neon  # ← Should be here
```

### Skeleton sync overwrites my rules

Your rules should be in `phpstan-local.neon`, not `phpstan.neon.dist`.
