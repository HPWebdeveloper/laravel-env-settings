# how to use

In this repo or any new repo from this template:

```bash
composer install
chmod +x bin/bootstrap
bin/bootstrap
```

Accept the inferred versions or enter your own

Choose whether to align composer.json constraints automatically

The script will:

- Rename namespaces/provider/config
- Update composer.json names and (optionally) version constraints
- Update CI matrix and mapping
- Update badges (if present)
- Run composer dump-autoload

# notes

Version inference is robust for typical constraints like ^8.1|^8.2|^8.3 and ^10.0|^11.0|^12.0. If you use more complex expressions (e.g., “>=8.1”), it will still extract 8.1, but there’s no guessing of upper bounds; you can override at the prompt.

If you pick Laravel majors beyond 10–12, the CI mapping block will keep a safe “Unsupported” fallback unless we extend the mapping table.
