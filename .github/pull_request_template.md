## Summary

Please provide a brief description of the change and its motivation.

## Checklist

- [ ] Public APIs use explicit array generics where applicable
      (e.g., `array<string,mixed>`, `array<int,string>`)
- [ ] Interface methods with array parameters/returns include PHPDoc generics
      (`@param array<string,mixed> $params`, `@return array<string,mixed>`)
- [ ] Redirects and responses return concrete Symfony `Response` types
      (e.g., use `redirect()->to(...)` for a `RedirectResponse`)
- [ ] JSON encoding in new code paths cannot return `false`
      (e.g., use `JSON_THROW_ON_ERROR` when appropriate)
- [ ] Composer files are normalized (`composer normalize --dry-run` is clean)
- [ ] Static analysis passes locally (`composer stan` or `vendor/bin/phpstan`)
- [ ] Tests pass locally (`composer test`)

## Notes

Add any additional context, migration notes, or follow-ups here.
